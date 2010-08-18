<?php

interface IActivityDefault {
	public function getType();
	public function getPermalink();
	public function getTitle();
	public function getId();
	public function getContent();
	public function setPermalink($href);
	public function setTitle($value);
	public function setId($value);
	public function setContent($value);
}

interface IActivityStatus extends IActivityDefault {
	
}

interface IActivityArticle extends IActivityDefault {
	public function getSummary();
	public function setSummary($value);
}

interface IActivityPhoto extends IActivityDefault {
	public function getThumbnail();
	public function getLargerImage();
	public function getImagePageUrl();
	public function getDescription();
	public function setThumbnail($href, $type, $width, $height);
	public function setLargerImage($href, $type, $width, $height);
	public function setImagePageUrl($href);
	public function setDescription($value);
}

interface IActivityBookmark extends IActivityDefault {
	public function getDescription();
	public function getTargetUrl();
	public function getBookmarkPageUrl();
	public function getTargetTitle();
	public function getThumbnail();
	public function setDescription($value);
	public function setTargetUrl($href);
	public function setBookmarkPageUrl($href);
	public function setTargetTitle($value);
	public function setThumbnail($href, $type, $width, $height);
}

interface IActivityAudio extends IActivityDefault {
	public function getAudioStream();
	public function getAudioPageUrl();
	public function getPlayerApplet();
	public function getDescription();
	public function setAudioStream($href, $type, $duration);
	public function setAudioPageUrl($href);
	public function setPlayerApplet($href, $type, $width, $height);
	public function setDescription($value);
}

interface IActivityVideo extends IActivityDefault {
	public function getThumbnail();
	public function getVideoStream();
	public function getVideoPageUrl();
	public function getPlayerApplet();
	public function getDescription();
	public function setThumbnail($href, $type, $width, $height);
	public function setVideoStream($href, $type, $duration);
	public function setVideoPageUrl($href);
	public function setPlayerApplet($href, $type, $width, $height);
	public function setDescription($value);
}

interface IMediaImageLink {
	public function getHref();
	public function getWidth();
	public function getHeight();
}

interface IMediaStreamLink {
	public function getHref();
	public function getDuration();
}