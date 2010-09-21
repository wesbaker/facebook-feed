Facebook Feed
=============

A PHP Class that builds a Facebook feed as an unordered list.

Usage
-----

Include the php file

	include_once('/includes/facebook_feed.php');
	
Create a new `Facebook_feed` object and pass in the Facebook username of the feed you want to build.

	$facebook_feed = new Facebook_feed("wesbaker");
	
Print the generated feed and either pass the number of posts you'd like returned or leave the parameters blank for three posts

	echo $facebook_feed->get(3);
	
License 
-------

Copyright (c) 2010 Wes Baker<br />
MIT License