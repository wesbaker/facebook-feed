<?php

/**
 * Directory where the class can cache the feed and images
 */
define("CACHE_DIRECTORY", $_SERVER['DOCUMENT_ROOT'].'/includes/facebook_cache/');

/**
 * Length of time in minutes to cache the feed
 * Default length is 5
 */
define("FEED_CACHE_TIME", 5);

/**
 * Length of time in minutes to cache the images
 * Default length is 1440 (full day)
 */
define("IMAGE_CACHE_TIME", 1440);

/**
 * Facebook Feed
 *
 * @version 1.0
 * @author Wes Baker <wes@wesbaker.com>
 * @license MIT License
 */
class Facebook_feed
{
	private $username;
	
	public function __construct($username)
	{
		$this->username = $username;
	}

	/**
	 * Build a permalink to the story/post
	 * @param integer $story_id The story/post ID from the Graph API
	 * @return string URL of the story/post
	 * @since 1.0
	 */
	private function _permalink($story_id)
	{
		$story_id = explode("_", $story_id);
		$story_id = $story_id[1];
		return "http://www.facebook.com/" . $this->username . "?v=wall&story_fbid=$story_id&ref=mf";
	}

	/**
	 * Figure out what the cached image's path would be, check if it exists and cache it if it doesn't
	 * @param string $user_id The user's ID from the Graph API
	 * @return string URL of the cached image's
	 * @since 1.0
	 */
	private function _cache_image($user_id)
	{
		$image_cache = "images/";
		$this->_build_cache_directory($image_cache);
		$cached_image = CACHE_DIRECTORY.$image_cache."$user_id.jpg";

		if ( ! file_exists($cached_image) || (((time() - filemtime($cached_image)) / 60) > IMAGE_CACHE_TIME)) {
			$image = file_get_contents("http://graph.facebook.com/" . $user_id . "/picture/");
			file_put_contents($cached_image, $image);
		}

		$image_url = str_replace($_SERVER["DOCUMENT_ROOT"], "", $cached_image);

		return $image_url;
	}

	/**
	 * Builds cache directory
	 * @param string $directory_name (optional) the name of the directory to build within the cache
	 * @since 1.0
	 */
	private function _build_cache_directory($directory_name = "")
	{
		if(!is_dir(CACHE_DIRECTORY.$directory_name))
		{
			if (!mkdir(CACHE_DIRECTORY.$directory_name, 0777, true))
			{
				echo "Unable to make cache directory " . CACHE_DIRECTORY.$directory_name . ", please create it and chmod 777";
				exit;
			}
		}

		if (!is_writable(CACHE_DIRECTORY.$directory_name))
		{
			echo "Cache directory " . CACHE_DIRECTORY.$directory_name . " is not writable please chmod 777";
			exit;
		}
	}

	/**
	 * Builds the facebook feed from the username given when constructed
	 * @since 1.0
	 */
	private function _build($limit) {
		// Load in JSON and decode
		$json = json_decode(file_get_contents('http://graph.facebook.com/' . $this->username . '/feed?limit=' . $limit));

		$facebook_feed = "";

		for ($i=0; $i < $limit; $i++) { 
			$current_post = $json->data[$i];

			// Get the user's data
			$user = json_decode(file_get_contents('http://graph.facebook.com/' . $current_post->from->id));

			$post = "<li>";
			$post .= "<img src='" . $this->_cache_image($current_post->from->id) . "' class='image-right' width='50' height='50' alt='' />";
			$post .= "<strong><a href='" . $user->link . "'>" . $current_post->from->name . "</a></strong> ";
			$post .= $this->_truncate_text($current_post->message, 140);
			$post .= "<small><a href='" . $this->_permalink($current_post->id) . "'>" . $this->_relative_time($current_post->created_time) . "</a></small>";
			$post .= "</li>";

			$facebook_feed .= $post;
		}

		file_put_contents(CACHE_DIRECTORY.'rendered_feed.txt', $facebook_feed);
	}

	/**
	 * Public function that gets the facebook feed
	 * @param integer $limit The number of items you want to see in the feed
	 * @return string The facebook feed
	 * @since 1.0
	 */
	public function get($limit = 3)
	{
		$cache_file = CACHE_DIRECTORY.'rendered_feed.txt';

		// If the cached version doesn't exist, build it
		if ( ! file_exists($cache_file) || (((time() - filemtime($cache_file)) / 60) >= FEED_CACHE_TIME)) {
			$this->_build($limit);
		}
		
		return file_get_contents($cache_file);
	}
	
	
	// Utility Classes ======================================================
	
	/**
	 * Truncates text down to a certain character limit
	 * @param string $text The text to truncate
	 * @param integer $limit The number of characters to show
	 * @param string $append The string inserted at the end of a truncated string
	 * @since 1.0
	 */
	private function _truncate_text($text, $limit, $append='...') {
	     if(strlen($text) > $limit) {
	          $text = substr($text, 0, $limit);
	          $text .= $append;
	     }
	     return $text;
	}

	/**
	 * Given a date, returns a relative time (e.g. about 3 days ago)
	 * @param string $date A string representation of a date
	 * @since 1.0
	 */
	private function _relative_time($date) {
		$valid_date = (is_numeric($date) && strtotime($date) === FALSE) ? $date : strtotime($date);
		$diff = time() - $valid_date;
		if ($diff > 0) {
			if ($diff < 60) {
				return $diff . " second" . $this->_plural($diff) . " ago";
			}
			$diff = round($diff / 60);

			if ($diff < 60) {
				return $diff . " minute" . $this->_plural($diff) . " ago";
			}
			$diff = round($diff / 60);

			if ($diff < 24) {
				return $diff . " hour" . $this->_plural($diff) . " ago";
			}
			$diff = round($diff / 24);

			if ($diff < 7) {
				return "about " . $diff . " day" . $this->_plural($diff) . " ago";
			}
			$diff = round($diff / 7);

			if ($diff < 4) {
				return "about " . $diff . " week" . $this->_plural($diff) . " ago";
			}

			return "on " . date("F j, Y", strtotime($valid_date));
		} else {
			if ($diff > -60) {
				return "in " . -$diff . " second" . $this->_plural($diff);
			}
			$diff = round($diff / 60);

			if ($diff > -60) {
				return "in " . -$diff . " minute" . $this->_plural($diff);
			}
			$diff = round($diff / 60);

			if ($diff > -24) {
				return "in " . -$diff . " hour" . $this->_plural($diff);
			}
			$diff = round($diff / 24);

			if ($diff > -7) {
				return "in " . -$diff . " day" . $this->_plural($diff);
			}
			$diff = round($diff / 7);

			if ($diff > -4) {
				return "in " . -$diff . " week" . $this->_plural($diff);
			}	

			return "on " . date("F j, Y", strtotime($valid_date));
		}
	}
	
	/**
	 * Checks if a value is 1 or not. If it's one it's not plural, otherwise it is.
	 * @param integer $num The number to check for plurality
	 * @since 1.0
	 */
	private function _plural($num) {
		if ($num != 1) { return "s"; }
	}
}