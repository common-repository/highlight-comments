<?php
function hc_options_page(){
//	global $wpdb;
	if (isset($_POST['update_options'])) {
		check_admin_referer('highlight-comments-update-options'); 
		$options['author_style'] = trim($_POST['author_style'], '{}');
		$options['author_all_style'] = trim($_POST['author_all_style'], '{}');
		$options['reply_style'] = trim($_POST['reply_style'], '{}');
		
		$options['list_no_highlighted_comments'] = trim($_POST['list_no_highlighted_comments']);
		$options['list_highlighted_comments'] = trim($_POST['list_highlighted_comments']);
		
		$options['active_author'] = isset($_POST['active_author']) ? true: false;
		$options['active_author_all'] = isset($_POST['active_author_all']) ? true: false;
		$options['active_user'] = isset($_POST['active_user']) ? true: false;
		$options['active_list'] = isset($_POST['active_list']) ? true: false;
		
		update_option('hc_highlight_comments', $options);
		echo '<div class="updated fade"><p>' . __('Options saved') . '!</p></div>';
	} else {
		$options = get_option('hc_highlight_comments');
	}
	
	?>
		<div class="wrap">
		<h2><?php echo __('Highlight-Comments Options'); ?></h2>
		<form method="post" action="options-general.php?page=highlight-comments/hc_highlight-comments-admin.php">
		<p><?php _e('Define here the css-related style information that will be added to the comments automatically.') ?></p>
		<fieldset class="options">
		<legend class="hidden">CSS Info</legend>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row"><label for="author_style"><?php _e('comments written by author:') ?></label></th>
				<td><textarea name="author_style" id="author_style" rows="4" cols="38"><?php echo $options['author_style']; ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="author_all_style"><?php _e('comments written by other authors:') ?></label></th>
				<td><textarea name="author_all_style" id="author_all_style" rows="4" cols="38"><?php echo $options['author_all_style']; ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="reply_style"><?php _e('"useful"-marked comments:') ?></label></th>
				<td><textarea name="reply_style" id="reply_style" rows="4" cols="38"><?php echo $options['reply_style']; ?></textarea></td>
			</tr>
		</table>
		</fieldset>
		
		<h3>Message below post</h3>
		<p><?php _e('Define here how the information on your Blog will be shown.') ?></p>
		<fieldset class="options">
		<legend class="hidden">Variables</legend>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row"><label for="list_no_highlighted_comments"><?php _e('Message if no comment is highlighted:') ?></label></th>
				<td><textarea name="list_no_highlighted_comments" id="list_no_highlighted_comments" rows="4" cols="38"><?php echo $options['list_no_highlighted_comments']; ?></textarea></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="list_highlighted_comments"><?php _e('Message if comments are highlighted:') ?></label></th>
				<td><textarea name="list_highlighted_comments" id="list_highlighted_comments" rows="4" cols="38"><?php echo $options['list_highlighted_comments']; ?></textarea><br />
				<?php _e('Use %NUMBER_OF_COMMENTS% and %COMMENTS_LIST% as variables.') ?></td>
			</tr>
		</table>
		</fieldset>
		
		<h3>Enable features</h3>
		<p><?php _e('Define which features you want to use.') ?></p>
		<fieldset class="options">
		<legend class="hidden">Features</legend>
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row" class="th-full"><label for="active_author">
				<input type="checkbox" id="active_author" name="active_author" value="1" <?php if($options['active_author']) echo 'checked="checked"' ?> />
				<?php _e('Activate highlighting of comments by the author of the post.') ?></label></th>
			</tr>
			<tr valign="top">
				<th scope="row" class="th-full"><label for="active_author_all">
				<input type="checkbox" id="active_author_all" name="active_author_all" value="1" <?php if($options['active_author_all']) echo 'checked="checked"' ?> />
				<?php _e('Activate highlighting of comments by other authors of the blog.') ?></label></th>
			</tr>
			<tr valign="top">
				<th scope="row" class="th-full"><label for="active_user">
				<input type="checkbox" id="active_user" name="active_user" value="1" <?php if($options['active_user']) echo 'checked="checked"' ?> />
				<?php _e('Activate highlighting of comments published by guests.') ?></label></th>
			</tr>
			<tr valign="top">
				<th scope="row" class="th-full"><label for="active_list">
				<input type="checkbox" id="active_list" name="active_list" value="1" <?php if($options['active_list']) echo 'checked="checked"' ?> />
				<?php _e('Activate the listing of all highlighted Comments below each post.') ?></label></th>
			</tr>
		</table>
		</fieldset>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Update') ?>" /></div>
		<?php if(function_exists('wp_nonce_field')) wp_nonce_field('highlight-comments-update-options'); ?>
		</form>    		
	</div>
	<?php
}

function hc_edit_page() {
?>
		<div class="wrap">
		<h2><?php echo __('Highlight-Comments'); ?></h2>
<?php
	if(isset($_GET['action']) and $_GET['action'] == 'highlight') {
	    $post_id = intval($_GET['post']);
	    $comment_id = intval($_GET['comment']);
		$post_meta = get_post_custom_values('highlighted-comments', $post_id);
		echo '<div class="updated fade"><p>';
		if(!isset($post_meta)) {
			add_post_meta($post_id, 'highlighted-comments', $comment_id);
			 _e('Comment highlighted');
		} elseif(in_array($comment_id, $list = hc_process_highlighted_comments_list($post_meta))) {
			delete_post_meta($post_id, 'highlighted-comments');
			unset($list[array_search($comment_id, $list)]);
			add_post_meta($post_id, 'highlighted-comments', implode(',', $list));
			_e('This comment is no longer highlighted');
		} else {
			$list = hc_process_highlighted_comments_list($post_meta);
			delete_post_meta($post_id, 'highlighted-comments');
			if(empty($list[0]))
				$list = array($comment_id);
			else
				$list[] = $comment_id;
			add_post_meta($post_id, 'highlighted-comments', implode(',', $list));
			_e('Comment highlighted');
		}
		echo '</p><p><a href="#" onclick="javascript:history.go(-1); return false;">' . __('Go back') . '</a></p></div>';
	} else {
		_e('Nothing to show here.');
	}
}

function hc_options() {
	if(current_user_can('manage_options'))
		add_options_page(__('Highlight Comments'), __('Highlight Comments'), 1, __FILE__, 'hc_options_page');
	add_management_page('Highlight Comments', 'Highlight Comments', 5, __FILE__, 'hc_edit_page');
}

add_action('admin_menu', 'hc_options');

function hc_init() {
	$options = get_option('hc_highlight_comments');
	if(!isset($options['author_style']))
		$options['author_style'] = 'background: #ffffcc; border: dashed black 1px;';
	if(!isset($options['author_all_style']))
		$options['author_all_style'] = 'background: #ffffcc;';
	if(!isset($options['reply_style']))
		$options['reply_style'] = 'background: #ffffcc;';
	if(!isset($options['list_no_highlighted_comments']))
		$options['list_no_highlighted_comments'] = 'There are currently no comments highlighted.';
	if(!isset($options['list_highlighted_comments']))
		$options['list_highlighted_comments'] = 'There are currently %NUMBER_OF_COMMENTS% comments highlighted: %COMMENTS_LIST%.';
	if(!isset($options['active_author']))
		$options['active_author'] = true;
	if(!isset($options['active_author_all']))
		$options['active_author_all'] = true;
	if(!isset($options['active_user']))
		$options['active_user'] = true;
	if(!isset($options['active_list']))
		$options['active_list'] = true;
		
	update_option('hc_highlight_comments', $options);
}

add_action('admin_menu', 'hc_init');
?>