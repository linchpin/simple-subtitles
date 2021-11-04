=== Simple Subtitles ===
Contributors: desrosj, linchpin_agency, aware, nateallen
Tags: subtitles, subtitle, subheading, title
Requires at least: 3.5
Tested up to: 5.8.1
Stable tag: 3.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Define a subtitle on any post, page, or custom post type.

== Description ==

Simple subtitles adds a field on posts, pages, or custom post types for adding a subtitle. Post types can be enabled/disabled in the writing settings section.

There are several functions in the plugin that allow you to easily get subtitles for different contexts. These functions mirror the functions in core for titles.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simple-subtitles` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Writing screen to configure enable subtitles for post types. Pages will be enabled by default.

== Other Notes ==

= If you want to display the subtitle in your theme on your own, use the following filter: =
`<?php
    function mytheme_disable_subtitle_display( $show ) {
        return false;
    }
    add_filter( 'simple_subtitle_auto', 'mytheme_disable_subtitle_display' );
?>`

= By default, only <span> tags are allowed in subtitles (with only class and style attributes). There is a filter this. =
`<?php
    function mytheme_simple_subtitle_allowed_html( $allowed ) {
        $allowed['strong'] = array();
        $allowed['div'] = array(
            'class' => true,
        );

        return $allowed;
    }
    add_filter( 'simple_subtitle_allowed_html', 'mytheme_simple_subtitle_allowed_html' );

?>`
For more info on this, read into [the wp_kses functions](https://codex.wordpress.org/Function_Reference/wp_kses "the wp_kses functions").

= Get the subtitle. =
`<?php $subtitle = get_the_simple_subtitle( $post_id ); ?>`

= Display the current post's subtitle. Should be used within the loop. =
`<?php $subtitle = the_simple_subtitle( $before = '', $after = '', $echo = true ); ?>`

= Get the subtitle for use in an HTML attribute. =
`<?php
    $args = array(
        'before' => '',
        'after' =>  '',
        'echo' => true
    );
    $subtitle = the_simple_subtitle_attribute( $args );
?>`

== Roadmap ==

* Add a column in the admin showing subtitles.
* Add a setting for selecting the subtitle HTML tag.

== Frequently Asked Questions ==

= Does this work with the Classic Editor and the Block Editor? =

Yes, this plugin will work for the Classic Editor or the Block Editor. In the Classic Editor, the subtitle field is below the title field. In the Block Editor, there is a Subtitle field in page settings panel.

== Screenshots ==

1. Simple Subtitles on the edit screen.
2. Default Simple Subtitle display on the front end.

== Changelog ==

= 3.0.0 =
* Add support for the block editor

= 2.1.1 =
* Fix bug where the automatic display of simple subtitles could not be turned off via filter.

= 2.1 =
* Added upgrade class for processing any upgrades that happen.
* Upgrade all meta keys on sites using an old install of the plugin.

= 2.0 =
* Hello world! Subtitles for all.