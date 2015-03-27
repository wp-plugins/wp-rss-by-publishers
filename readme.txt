=== Plugin Name ===
Contributors: robert.antofe
Tags: publishers, rss aggregator
Requires at least: 3.0.1
Tested up to: 4.1.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP RSS By Publishers is built the way an aggregator should be.


== Description ==

WP RSS By Publishers is built the way an aggregator should be.

1. You create Publishers
2. You add feeds to each Publisher
3. You create a rule to assign feed posts to you categories based on:
- Specific feed (or all of them
- Specific tags/ categories that come with the feed or any (tag/category independent)

Create a system cron using the following script to pull new content from feed: wsys-feed-cron.php. This way you can add a lot of feeds without impacting on performance. Also you have control on the pulling intervals.

Features
*	Configure the minimum size for the imported pictures
*	Add the option of redirecting the user to original post
*	If you activate redirection to original post, you can also configure the utm_source in case you wish the publisher to know you as a referral
*	Works with RSS 2.0, Atom, etc.
*	Straightforward.


== Installation ==
*	Upload `wp-rss-by-publisher` to the `/wp-content/plugins/` directory
or
*	Activate the plugin through the 'Plugins' menu in WordPress
*	CRON:
	Use the DISABLE WP_CRON constant to disable the wordpress cron and configure a system cron by using the second statement.
	define('DISABLE_WP_CRON', true);
	/15 * * * wget -q -O – http://yourdomain.com/wp-cron.php?doing_wp_cron

== Frequently Asked Questions ==
= Are there any settings available? =
Yes:
1. Min width - Minimum dimension for featured images in posts.
2. Min height - Minimum dimension for featured images in posts.
3. Redirect - if you wish to redirect the user to the original post
3. UTM Source - utm_source parameter that is appended to the source urls

= Is this plugin extracting images? =
Yes, only for the featured image so that in the listings it will have an image to display.

= Where is this plugin extracting the featured image from? =
It searches for it in the body of the feed item post.

== Changelog ==
= 1.0 (2015-03-18) =
* Add publishers
* Add feeds for each publisher
* Add rules for at a publisher level and at a feed level 

== Upgrade Notice ==
= No upgrade available. =

== Screenshots ==
1. Publishers list
2. Add Publisher
3. Add Feed
4. Add Rule
5. All posts