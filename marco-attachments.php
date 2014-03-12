<?php
/*
Plugin Name: Marco Attachments Plugin
Description: Manage attachments' parent-children relationship
Author: mimosafa
Version: 0.1
Author URI: http://mimosafa.me
*/

new marco_attachments();

class marco_attachments {

	const VER = '0.1'; // plugin version

	function __construct() {
		add_action( 'load-post.php', array( $this, 'init' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'wp_ajax_return_ajax_anna', array( $this, 'return_ajax_anna' ) );
		add_action( 'edit_attachment', array( $this, 'update_post_parent' ) );
	}

	/**
	 * initialize
	 */
	function init() {
		$screen = get_current_screen();
		if ( 'attachment' === $screen->post_type ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_footer', array( $this, 'find_posts_div' ) );
		}
	}

	/**
	 * enqueue style and script
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'marco', plugins_url( 'css/style.css', __FILE__ ), array(), self::VER );
		wp_enqueue_script( 'marco', plugins_url( 'js/marco.js', __FILE__ ), array( 'media' ), self::VER, true );
		$_id = get_the_ID();
		wp_localize_script( 'marco', 'MARCO_ATT', array(
			'postID'   => $_id,
			'parentID' => get_post( $_id )->post_parent,
			'endpoint' => admin_url( 'admin-ajax.php' )
		) );
	}

	/**
	 * find posts popup in admin footer
	 */
	function find_posts_div() { ?>
<form method="post" action="" id="marco-find-posts-form">
<?php
			find_posts_div(); ?>
</form>
<?php
	}

	/**
	 * update attachment's post parent
	 */
	function update_post_parent( $post_id ) {
		if ( !isset( $_POST['_marco_nonce'] ) || !wp_verify_nonce( $_POST['_marco_nonce'], plugin_basename( __FILE__ ) ) )
			return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		$exists_parent = wp_get_post_parent_id( $post_id );
		if ( !isset( $_POST['marco-post-parent'] ) || $exists_parent == $_POST['marco-post-parent'] )
			return;
		$new_parent = (int) $_POST['marco-post-parent'];
		$post_type = get_post_type( $new_parent );
		if ( 'page' === $post_type ) {
			if ( !current_user_can( 'edit_page', $new_parent ) )
				return;
		} else {
			if ( !current_user_can( 'edit_post', $new_parent ) )
				return;
		}
		$id = wp_update_post( array(
			'ID' => $post_id,
			'post_parent' => $new_parent
		), true );
	}

	/**
	 * add meta box
	 */
	function add_meta_box() {
		add_meta_box(
			'marco-attachment-parent',
			_x( 'Uploaded to', 'column name' ),
			array( $this, 'meta_box_cb' ),
			'attachment',
			'side',
			'default'
		);
	}

	/**
	 * meta box inner
	 *
	 * @uses return_anna_html ...display existed parent post title and post type
	 */
	function meta_box_cb( $post ) {
		$parent = $post->post_parent;
		echo $this->return_anna_html( $parent, 'marco-s-anna' );
		/**
		 * edit action html
		 */
		if ( current_user_can( 'edit_post', $post ) ) {
			wp_nonce_field( plugin_basename( __FILE__ ), '_marco_nonce' ); /* nonce field */ ?>
<input type="hidden" id="marco-post-parent" />
<div id="marco-controls">
<a href="#" id="marco-cancel"><?php echo __( 'Cancel' ); ?></a>
<a href="#" id="marco-find-posts" class="hide-if-no-js"><?php echo __( 'Find Parent' ); ?></a><?php
			if ( $parent ) { ?>
<span> | <span>
<a href="#" id="marco-separate-post"><?php echo __( 'Separate Parent' ); ?></a><?php
			} ?>
</div><?php
		}
	}

	/**
	 * ajax action, return new parent post title and post type
	 *
	 * @uses return_anna_html
	 */
	function return_ajax_anna() {
		$anna = '';
		if ( $id = $_POST['annaid'] )
			$anna .= $this->return_anna_html( $id, 'marco-s-new-anna' );
		header( 'Content-Type: application/html; charset=utf-8' );
		echo $anna;
		die();
	}

	/**
	 * return html for attachments' meta box and for ajax
	 *
	 * @param int $parent
	 * @param string $pid
	 *
	 * @return string html
	 */
	function return_anna_html( $parent, $pid ) {
		$anna_html = '';
		if ( !$parent ) {
			$anna_html .= _e( '(Unattached)' );
		} else {
			$title = get_the_title( $parent );
			$parent_type = get_post_type_object( get_post_type( $parent ) );
			$anna_html .= $parent_type->labels->singular_name . ': <strong>';
			if ( current_user_can( 'edit_post', $parent ) && $parent_type->show_ui )
				$anna_html .= '<a href="' . get_edit_post_link( $parent ) . '">' . $title . '</a>';
			else
				$anna_html .= $title;
			$anna_html .= '</strong>';
		}
		return '<p id="' . esc_attr( $pid ) . '">' . $anna_html . '</p>';
	}

}