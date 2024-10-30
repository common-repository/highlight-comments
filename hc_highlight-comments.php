<?php
/*
Plugin Name: Highlight Comments
Plugin URI: http://www.jan-baier.de/plugins/highlight-comments/
Description: Highlights both comments posted by the author and those that are set as "useful".
Version: 1.3
Author: Jan Baier
Author URI: http://www.jan-baier.de/
*/ 

/*
Copyright 2008  Jan Baier  (http://www.jan-baier.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

function hc_show_highlight_link($text = '', $before = '', $after = '') {
	global $post, $comment;
	if(!current_user_can('edit_post', $post->ID))
		return;
	$location = get_option('siteurl') . "/wp-admin/tools.php?page=highlight-comments/hc_highlight-comments-admin.php&amp;action=highlight&amp;post=$post->ID&amp;comment=$comment->comment_ID";
	return "$before <a href='$location'>$text</a> $after";
}

function hc_process_highlighted_comments_list($highlighted_comments) {
	$list = '';
	foreach($highlighted_comments as $value) {
		if(!empty($value))
			$list .= ',' . $value;
	}
	if(empty($list))
	  return array();
	$list = substr($list, 1);
	$list = explode(',', trim($list));

	if(!function_exists('trim_value')) {
		function trim_value(&$value) {
			$value = intval(trim($value));
		}
	}

	array_walk($list, 'trim_value');

	return $list;
}

function hc_is_highlighted() {
	global $comment;
	$post_meta = get_post_custom_values('highlighted-comments');
	if(!isset($post_meta))
		return false;
	if(in_array($comment->comment_ID, hc_process_highlighted_comments_list($post_meta)))
		return true;
	return false;
}

function hc_is_an_author($user_id) {
	if(!$user_id)
		return false;
	$user_info = get_userdata($user_id);
	if($user_info->user_level >= 5)
		return true;
	return false;
}

function hc_highlight_comments($content) {
	if(!is_single() and !is_page())
		return $content;
	global $comment;
	if(strtolower($comment->comment_author_email) == strtolower(get_the_author_email())) {
		$options = get_option('hc_highlight_comments');
		if($options['active_author'])
			return '<div style="'.$options['author_style'].'"><p>' . $content . '</p></div>';
		else
			return $content;
	} elseif(hc_is_an_author($comment->user_id)) {
		$options = get_option('hc_highlight_comments');
		if($options['active_author_all'])
			return '<div style="'.$options['author_all_style'].'"><p>' . $content . '</p></div>';
		else
			return $content;
	} elseif(hc_is_highlighted()) {
		$options = get_option('hc_highlight_comments');
		if($options['active_user'])
			return '<div style="'.$options['reply_style'].'"><p>' . $content . '</p></div>' . hc_show_highlight_link('Do not any longer highlight this comment.', '<p><small>', '</small></p>');
		else
			return $content;
	}
	$options = get_option('hc_highlight_comments');
	return '<p>' . $content . (($options['active_user']) ? hc_show_highlight_link('Highlight this comment.', '<p><small>', '</small></p>'): '</p>');
}

add_filter('comment_text', 'hc_highlight_comments', 20);

function hc_count_highlighted_comments() {
	$post_meta = get_post_custom_values('highlighted-comments');
	if(!isset($post_meta))
	  return '0';
	return count(hc_process_highlighted_comments_list($post_meta));
}

function hc_list_highlighted_comments($content) {
	global $post;
	if((!is_single() and !is_page()) or (($post->comment_status == 'closed') and !($post->comment_count)))
		return $content;
	$options = get_option('hc_highlight_comments');
	if(!$options['active_list'])
		return $content;
	$post_meta = get_post_custom_values('highlighted-comments');
	if(!isset($post_meta))
	  return $content . $options['list_no_highlighted_comments'];
	$link = '';
	$post_meta_backup = hc_process_highlighted_comments_list($post_meta);
	if(!count($post_meta_backup))
	  return $content . $options['list_no_highlighted_comments'];
	sort($post_meta_backup);
	foreach($post_meta_backup as $value)
		$link .= ', <a href="#comment-' . $value . '">' . $value . '</a>';
	return $content . str_replace(array('%COMMENTS_LIST%', '%NUMBER_OF_COMMENTS%'), array(substr($link, 2), count($post_meta_backup)), $options['list_highlighted_comments']);
}

add_filter('the_content', 'hc_list_highlighted_comments');

if(is_admin())
	require(dirname(__FILE__).'/hc_highlight-comments-admin.php');
?>