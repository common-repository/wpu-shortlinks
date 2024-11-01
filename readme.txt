=== WPU ShortLinks ===
Contributors: parselearn
Donate link: http://wpu.ir/
Tags: WPU, WPU ShortLinks, WPU URL Shortener, ShortLink , shortener link , link , wpu , url shortener , yourls , custom url , short link, short url, shorturl ,  url generator, url , uri shortner , social url , linker , Twitter, Facebook, Google+, Linkedin, Tumblr, Pinterest, Reddit, Telegram, Skype, WhatsApp, Pocket, Email Sharing, Social Sharing, social share, twitter button, twitter facebook share, twitter share, bookmark, bookmarking, bookmarks,button,facebook share, google, google +1, google plus, google plus one, Like, plus 1, plus one, Share, share button, share buttons, share links, share this, Shareaholic, sharedaddy, sharethis, sharing, shortcode, sociable, social, social bookmarking, social bookmarks, social share, social sharing, tweet, tweet button, twitter button, twitter share, widget
Requires at least: 4.0
Tested up to: 4.7
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WPU.IR is a powerful and Free URL Shortener Service.

== Description ==

[WPU.IR](http://wpu.ir/) is a powerful and Free URL Shortener Service.

* Generate shortlinks with bulk action
* Request shortlinks with admin bar shortcut
* Adds very simple social sharing buttons for Twitter, Facebook, Google+, Linkedin, Tumblr, Pinterest, Reddit, Telegram, Skype, WhatsApp, Pocket and Email to the end of your posts
* [Get free API key](http://wpu.ir/dashboard/webservice)

== Installation ==

1. Upload `wpu-shortlinks` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
= Shortcode (use in post editor): =
`[wpu]Post ShortLink[/wpu]`

= Specific Post =
`[wpu id=74]Wordpress for life :)[/wpu]`

= Function: =
`wpu_shortlink(post_id,display)`

= Use in post loop: =
`<a href="<?php wpu_shortlink() ?>">ShortLink</a>`

= Specific Post: =
`<a href="<?php wpu_shortlink(74) ?>">ShortLink</a>`

= Request custom URI: =
`$ShortLink = wpu_get_shortlink("http://www.google.com");`

= Social Sharing (Use in post loop) =
`wpu_social_sharing(display)`

== Screenshots ==

1. Settings
2. Request ShortLink With Admin Bar Shortcut
3. Generate ShortLinks with Bulk Action (WordPress 4.7 & Up)
4. Post ShortLink of Publish Widget
5. Show ShortLink & Social Sharing Buttons of Post 1
6. Show ShortLink & Social Sharing Buttons of Post 2

== Changelog ==

= 2.1 =
Optimize for old WordPress version

= 2.0 =
* Optimize plugin for new wpu.ir API
* Speed up for get shortlink
* New social sharing buttons

= 1.1.1 =
Remove BOM Character from plugin files.

= 1.1 =
* Fixed Bug Request ShortLink With Admin Bar Menu
* Fixed Bug ShortCode Generator Post Editor Button
* Two Method Show Automatic ShortLink of Post
* Adds Social Sharing Buttons

= 1.0 =
* Generate ShortLinks from Post Content URLs
* Generate ShortLinks with Bulk Action
* Request ShortLink With Admin Bar Menu

= 0.3 =
validate url fixed

= 0.2 =
Webservice bug fixed

= 0.1.4 =
* Add languages file
* Show ShortLink in admin posts

= 0.1.3 =
Fix bug for get post link

= 0.1.2 =
Fix bug for get post link

= 0.1.1 =
Add new function for custom request
`$ShortLink = wpu_get_shortlink("www.google.com");`

= 0.1 =
Start the project...

== Upgrade Notice ==

= 0.1.1 =
Add new function for custom request
`$ShortLink = wpu_get_ShortLink("www.google.com");`

= 0.1 =
* Start the project...