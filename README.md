# Simple Subtitles #

Easily add a subtitle to your post, pages and custom post types

## Description ##

Simple subtitles adds a field on posts, pages, or custom post types for adding a subtitle. Post types can be enabled/disabled in the writing settings section.

There are several functions in the plugin that allow you to easily get subtitles for different contexts. These functions mirror the functions in core for titles.

### If you want to display the subtitle in your theme on your own, use the following filter: ###

	<?php
	    function mytheme_disable_subtitle_display( $show ) {
	        return false;
	    }
	    add_filter( 'simple_subtitle_auto', 'mytheme_disable_subtitle_display' );
	?>

### By default, only span tags are allowed in subtitles (with only class and style attributes). There is a filter this. ###

	<?php
	    function mytheme_simple_subtitle_allowed_html( $allowed ) {
	        $allowed['strong'] = array();
	        $allowed['div'] = array(
	            'class' => true,
	        );

	        return $allowed;
	    }
	    add_filter( 'simple_subtitle_allowed_html', 'mytheme_simple_subtitle_allowed_html' );
	?>

For more info on this, read into [the wp_kses functions](https://codex.wordpress.org/Function_Reference/wp_kses).

### Get the subtitle ###

	<?php $subtitle = get_the_simple_subtitle( $post_id ); ?>

### Display the current post's subtitle. Should be used within the loop. ###

<?php $subtitle = the_simple_subtitle( $before = '', $after = '', $echo = true ); ?>

### Get the subtitle for use in an HTML attribute. ###

	<?php
	    $args = array(
	        'before' => '',
	        'after' =>  '',
	        'echo' => true
	    );
	    $subtitle = the_simple_subtitle_attribute( $args );
	?><h3>Roadmap</h3>


## Installation ##

1. Upload the plugin files to the /wp-content/plugins/simple-subtitles directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the Settings->Writing screen to configure enable subtitles for post types. Pages will be enabled by default.