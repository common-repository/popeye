=== wp-popeye ===
Contributors: stromchris
Donate link: http://chrp.es/
Tags: simple, gallery, inline, images, image, popeye, jquery, pictures, lightbox, usability, thumbnail, shortcode
Requires at least: 2.9
Tested up to: 3.0
Stable tag: trunk

Popeye presents images from the Wordpress media library as an elegant javascript inline gallery within your posts and pages. 

== Description ==

Popeye presents images from the Wordpress media library in a nice and elegant way within your posts and pages. Use it to save space when displaying a collection of images and offer your users a simple way to show a big version of your images without leaving the page flow.

It is based on Christoph Schuessler's jQuery.popeye which was designed as an alternative to the often-seen JavaScript image lightbox (see Lightbox 2, Fancybox or Colorbox, just to name a few). In contrast to them it does not employ a modal window to display the large images, thus disrupting the workflow of the user interacting with a webpage, but takes a different approach: not only allows it for browsing all thumbnails as well as the large images in a single image space, it also repects the page flow and stays anchored and rooted in the webpage at all times, thus giving a less disruptive user experience than modal windows.

The plugin is very easy to set up and integrates automatically into your posts or allows you to use a shortcode. It comes with several styles but can be also easily customized to integrate into your blog's design. 

== Installation ==

1. Unzip `popeye-X.X.zip` into `/wp-content/plugins/popeye` 
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Choose your insertion mode & style in the 'Settings' menu

== Screenshots ==

1. This is an example how popeye displays your images within the text using the example-green style. 
2. This is another one using example-blue.

== Changelog ==

= 0.2.5 =
* added [nopopeye] shortcode to suppress the automated insertion of popeye into every post for this specific post

= 0.2.4 =
* added navigation display mode (hover or permanent) as an option in the wordpress backend settings
* removed a bug: ppy could not display when using shortcodes due to a missing class-attribute set to ppy
* thanks to Raoni Del Persio from http://www.centralwordpress.com.br for giving feedback!

= 0.2.3 =
* allow custom order of images
* added include & exclude attributes for inclusion or removal of specific image ids
* checked wp 3.0 compatibility 
* added some simple usage help
* thanks to Raoni Del Persio from http://www.centralwordpress.com.br for sponsoring this release!

= 0.2.2 =
* replaced jQuery.popeye with current version 2.0.4
* now uses the description of an image as it's caption (or the caption as until now when the description is empty)
* solved the "only 5 pics" issue - see here: http://chrp.es/wp-popeye#comment-39
* now orders the images by title descending so you can edit the order by renaming the images (e.g. 10_bla, 11_blup, 12_foo, etc.)

= 0.2.1 =
* script is now PHP4 compatible
* replaced jQuery.popeye with current version 2.0.3, solves some IE6 issues
* added some links to the 'Manage Plugins' page of wp-admin
* fixed the "Enlarged size" dropdown on the settings page which jumped to "Large" when the current selection was "Original"


== Upgrade Notice ==

= 0.2.4 =
You can now select the navigation display mode from wp's backend

= 0.2.2 =
Now popeye can show more than 5 images attached to a post and images can be ordered.

= 0.2.1 =
This release allows wp-popeye to run with PHP4 and fixes some IE6 issues.

== Frequently Asked Questions ==

= Is there any integration with NextGEN gallery? =
Currently no. But you are very welcome to give me some insight on how the NextGEN gallery works and how I can hook up on their galleries and pictures.

= Does it work with PHP4? =
Finally, yes.
