<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 */

class EmbedController extends BaseController
{

    public function preDispatch() {
		if (!$this->_user) {
			throw new Stuffpress_NotFoundException("No user specified");
		}
			
		// If the page is private, go back with an error
		if (!$this->_admin && $this->_properties->getProperty('is_private')) {
			throw new Stuffpress_AccessDeniedException("This page has been set as private.");		
		}
    }
	
	public function indexAction() {
		$file  = $this->getRequest()->getParam("file");
		$this->_forward($file);
	}

	public function pageAction()
	{
		$count  = $this->getRequest()->getParam("count");
		$width  = $this->getRequest()->getParam("width");
		$height = $this->getRequest()->getParam("height");
		$count  = $count ? $count : 25;
		$host	= $this->_config->web->host;
		
		header("Content-type: text/javascript");
		header("Content-Disposition: attachment; filename=\"page.js\"");

		echo "document.write('<script src=\'http://$host/js/controllers/embed_page.js\' type=\'text/javascript\' /></script>');\r\n";
		echo "document.write(\"<div id='storytlr' style='width:{$width}px;height:{$height}px;'>\");\r\n";
		echo "document.write(\"<iframe id='storytlr_frame' src='" . $this->getUrl($this->_user->username, "/timeline/index/embed/page/count/$count") . "' width='100%' height='100%' scrolling='no' frameborder='0'>Your browser doe snot support this. Check the lifestream here.</iframe>\");\r\n";
		echo "document.write(\"</div>\");";

		ob_end_flush();
		die();
	}

	public function storiesAction()
	{
		$count = $this->getRequest()->getParam("count");
		$count = $count ? $count : 5;
		
		// Hit the cache
		$cache_id = "embed_stories_{$this->_user->id}_{$count}";
		if (!$this->_cache || !($script = $this->_cache->load($cache_id))) {

			// A few variable needed
			$host	= $this->_config->web->host;

			// Get the user properties
			$username	= $this->_user->username;

			// Get all the items; if we are an admin, we also get the hidden one
			$stories = new Stories();
			$items   = $stories->getStories($count, 0, false);

			$content = "<div id='storytlr_widget' class='storytlr_widget'>";
			if (count($items) == 0) {
				$content .= "<p>$username has not created any story yet.</p>";
			} else {
				foreach($items as $item) {

					$item['permalink'] = Stuffpress_Permalink::story($item['id'], $item['title']);

					$date_from = date("F j, Y", $item['date_from']);
					$date_to   = date("F j, Y", $item['date_to']);

					$item_content  = "<table><tr>";
					$item_content .="<td class='thumbnail'><a href='http://$username.$host/story/{$item['permalink']}'>";

					if ($item['thumbnail']) {
						$item_content .= "<img src='" . $this->getUrl($username, "/thumbnail/{$item['thumbnail']}") . "'>";
					} else {
						$item_content .= "<img src='" . $this->getUrl($username, "/images/book50.jpg") . "'>";
					}

					$item_content .= "</a></td>";
					$item_content .= "<td class='overview'>";
					$item_content .= "<div class='title'><a href='" . $this->getUrl($username, "/story/{$item['permalink']}") . "'>" . $this->escape($item['title']) . "</a></div>";
					$item_content .= "<div class='subtitle'>" . $this->escape($item['subtitle']) . "</div>";
					$item_content .= "<div class='date'>$date_from to $date_to</div>";
					$item_content .= "</td>";
					$item_content .= "</tr></table>";
					$content .= $item_content;
				}
			}
			$content .= "</div>";

			$script = "document.write(\"<link href='http://$host/style/embed_stories.css' media='screen, projection' rel='stylesheet' type='text/css' />\");\r\n"
			. "document.write('<script src=\'http://$host/js/controllers/embed_story.js\' type=\'text/javascript\' /></script>');\r\n"
			. "document.write(\"<div id='storytlr'>\");\r\n"
			. "document.write(\"<h1>". ucfirst($username) . "'s Stories</h1>\");\r\n"
			. "document.write(\"". $content ."\");\r\n"
			. "document.write(\"<div class='bar'><a href='http://$host'><img src='http://$host/images/powered2.png'></a></div>\");\r\n"
			. "document.write(\"</div>\");";

			if ($this->_cache) {
				$this->_cache->save($script, $cache_id, array("stories_{$this->_user->id}"), 300);
			}
		}

		header("Content-Disposition: attachment; filename=\"stories.js\"");
		header("Content-type: text/javascript; charset=UTF-8");
		echo $script;
		ob_end_flush();
		die();
	}

	public function widgetAction()
	{
		$count = $this->getRequest()->getParam("count");
		$count = $count ? $count : 5;
		$host  = $this->_config->web->host;
		
		// Hit the cache
		$cache_id = "embed_widget_{$this->_user->id}_{$count}";
		if (!$this->_cache || !($widget = $this->_cache->load($cache_id))) {

			// Get all the items; if we are an admin, we also get the hidden one
			$username	= $this->_user->username;
			$data    = new Data();
			$items   = $data->getLastItems($count, 0, false);

			$content = "<div id='storytlr_widget' class='storytlr_widget'><table>";
			foreach($items as $item) {
				$title		   = preg_replace("|([[:alpha:]]+://[^[:space:]]+[[:alnum:]/])|","<a href='\\1'>\\1</a>", $this->escape(strip_tags($item->getTitle())));
				$item_content  = "<tr>";
				$item_content .="<td class='icon'><a href='{$item->getLink()}'><img src='http://$host/images/{$item->getPrefix()}.png'></a></td>";
				$item_content .= "<td class='title'>$title</td>";
				$item_content .= "</tr>";
				$content .= $item_content;
			}
			$content .= "</table></div>";

			$widget = "document.write(\"<link href='http://$host/style/embed_widget.css' media='screen, projection' rel='stylesheet' type='text/css' />\");\r\n"
			. "document.write(\"<div id='storytlr'>\");\r\n"
			. "document.write(\"<h1>". ucfirst($username) . "'s Lifestream</h1>\");\r\n"
			. "document.write(\"<h2>Latest updates</h2>\");\r\n"
			. "document.write(\"". $content ."\");\r\n"
			. "document.write(\"<a href='" . $this->getUrl($username, "/") . "'>View all</a>\");\r\n"
			. "document.write(\"<div class='bar'><a href='http://$host'><img src='http://$host/images/powered2.png'></a></div>\");\r\n"
			. "document.write(\"</div>\");";

			if ($this->_cache) {
				$this->_cache->save($widget, $cache_id, array("content_{$this->_user->id}"), 300);
			}
		}

		// Output the result
		header("Content-Disposition: attachment; filename=\"widget.js\"");
		header("Content-type: text/javascript; charset=UTF-8");
		echo $widget;
		ob_end_flush();
		die();
	}

	public function storyAction()
	{
		$story_id  	= $this->getRequest()->getParam("id");
		
		// Hit the cache
		$cache_id = "embed_story_{$story_id}";
		if (!$this->_cache || !($script = $this->_cache->load($cache_id))) {

			//Verify if the requested user exist
			$stories 	= new Stories();
			$story 		= $stories->getStory($story_id);

			// If not, then return to the home page with an error
			if (!$story) {
				throw new Stuffpress_NotFoundException("Story $story_id does not exist");
			}

			// If the story is draft, go back with an error
			if ($story->is_hidden) {
				throw new Stuffpress_AccessDeniedException("This story has not been published yet.");
			}

			// Get the user properties
			$username	= $this->_user->username;
			$host		= $this->_config->web->host;

			// Get the data we need
			$id    = $story->id;
			$uid   = rand(0,100) . $id;
			$title = $story->title;
			$sub   = $story->subtitle;
			$image = $story->thumbnail;

			$script = "document.write('<link href=\'http://$host/style/embed_story.css\' media=\'screen, projection\' rel=\'stylesheet\' type=\'text/css\' />');\r\n"
			. "document.write('<script src=\'http://$host/js/controllers/embed_story.js\' type=\'text/javascript\' /></script>');\r\n"
			. "document.write('<div id=\'storytlr_embed\' onclick=\'showStory($uid);\' title=\'Click to view story\'>');\r\n"
			. "document.write('<div class=\'logo\'><img src=\'http://$host/images/coverlogo.png\' /></div>');\r\n"
			. "document.write('<div class=\'cover\'>');\r\n";

			if ($image)  $script .= "document.write('<img src=\'" . $this->getUrl($username, "/file/view/key/$image") . "\' class=\'cover\'>');\r\n";

			$script .= "document.write('</div>');\r\n"
			. "document.write('<div class=\'titles\'>');\r\n"
			. "document.write('<span class=\'title\' id=\'story_title\'>" . $this->escape($title) . "</span>');\r\n"
			. "document.write('<span class=\'subtitle\' id=\'story_subtitle\'>" . $this->escape($sub) . "</span>');\r\n"
			. "document.write('</div>');\r\n"
			. "document.write('</div>');\r\n"
			. "document.write('<div class=\'popoutwrapper\'><div class=\'popout\'><a href=\'" . $this->getUrl($username, "/story/view/id/$id"). "\' target=\'_blank\' >View in new window <img src=\'http://$host/images/popout.gif\' /></a></div></div>');\r\n"
			. "document.write('<div class=\'storytlr_mask\' id=\'storytlr_mask_$uid\'>');\r\n"
			. "document.write('</div>');\r\n"
			. "document.write('<div class=\'storytlr_container\' id=\'storytlr_container_$uid\'>');\r\n"
			. "document.write('<div id=\'storytlr_page\'>');\r\n"
			. "document.write('<div id=\'storytlr_control\'>');\r\n"
			. "document.write('<a href=\'javascript:hideStory($uid);\' title=\'Close story\'><img src=\'http://$host/images/close.gif\'/></a>');\r\n"
			. "document.write('</div>');\r\n"
			. "document.write('<iframe id=\'storytlr_frame\' src=\'". $this->getUrl($username, "/story/view/id/$id?embed=page"). "\' width=\'1050px\' height=\'620px\' scrolling=\'no\' frameborder=\'0\'>Your browser doe snot support this. Check the lifestream here.</iframe>');\r\n"
			. "document.write('</div>');\r\n"
			. "document.write('</div>');\r\n";

			if ($this->_cache) {
				$this->_cache->save($script, $cache_id, array("story_{$story_id}"), 300);
			}
		}

		header("Content-type: text/javascript");
		header("Content-Disposition: attachment; filename=\"widget.js\"");
		echo $script;
		ob_end_flush();
		die();
	}

	private function escape($string) {
		$string = preg_replace("/\s+/", " ", $string);
		$string = str_replace("&", "&amp;", $string);
		$string = str_replace('"', "&quot;", $string);
		$string = str_replace("'", "&apos;", $string);
		$string = str_replace("<", "&lt;", $string);
		$string = str_replace(">", "&gt;", $string);
		return $string;
	}
}
