<?php


class ActivityNS {
	const NS		= 'http://activitystrea.ms/spec/1.0/';
	const PREFIX		= 'activity';
	
	const VERB_ELEMENT			= 'verb';
	const OBJECT_ELEMENT		= 'object';
	const OBJECT_TYPE_ELEMENT	= 'object-type';
	const TARGET_ELEMENT		= 'target';
	
	const WIDTH_ATTRIBUTE		= 'width';
	const HEIGHT_ATTRIBUTE		= 'height';
	
	const FAVORITE_VERB			= 'http://activitystrea.ms/schema/1.0/favorite';
	const FOLLOWING_VERB		= 'http://activitystrea.ms/schema/1.0/follow';
	const LIKE_VERB				= 'http://activitystrea.ms/schema/1.0/like';
	const MAKE_FRIEND_VERB		= 'http://activitystrea.ms/schema/1.0/make-friend';
	const JOIN_VERB				= 'http://activitystrea.ms/schema/1.0/join';
	const PLAY_VERB				= 'http://activitystrea.ms/schema/1.0/play';
	const POST_VERB				= 'http://activitystrea.ms/schema/1.0/post';
	const SAVE_VERB				= 'http://activitystrea.ms/schema/1.0/save';
	const SHARE_VERB			= 'http://activitystrea.ms/schema/1.0/share';
	const TAG_VERB				= 'http://activitystrea.ms/schema/1.0/tag';
	const UPDATE_VERB			= 'http://activitystrea.ms/schema/1.0/update';
	
	const ARTICLE_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/article';
	const AUDIO_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/audio';
	const BOOKMARK_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/bookmark';
	const COMMENT_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/comment';
	const FILE_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/file';
	const FOLDER_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/folder.';
	const GROUP_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/group';
	const LIST_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/list';
	const NOTE_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/note';
	const PERSON_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/person';
	const PHOTO_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/photo';
	const PHOTO_ALBUM_OBJECT_TYPE	= 'http://activitystrea.ms/schema/1.0/photo-album';
	const PLACE_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/place';
	const PLAYLIST_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/product';
	const PRODUCT_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/review';
	const REVIEW_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/service';
	const STATUS_OBJECT_TYPE		= 'http://activitystrea.ms/schema/1.0/status';
	const VIDEO_OBJECT_TYPE			= 'http://activitystrea.ms/schema/1.0/video';
}