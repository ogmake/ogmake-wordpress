<?php
/**
 * Per-post override meta box with preview.
 *
 * @package Ogmake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta box.
 */
class Ogmake_Metabox {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 1 );
	}

	/**
	 * Add to every enabled post type.
	 *
	 * @return void
	 */
	public static function add() {
		$settings = Ogmake_Settings::get();
		foreach ( (array) $settings['post_types'] as $type ) {
			add_meta_box( 'ogmake', __( 'ogmake social image', 'ogmake-og-images' ), array( __CLASS__, 'render' ), $type, 'side' );
		}
	}

	/**
	 * Render.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render( $post ) {
		wp_nonce_field( 'ogmake_meta', 'ogmake_nonce' );
		$disable  = (bool) get_post_meta( $post->ID, '_ogmake_disable', true );
		$template = (string) get_post_meta( $post->ID, '_ogmake_template', true );
		$title    = (string) get_post_meta( $post->ID, '_ogmake_title', true );
		$preview  = Ogmake_Url::for_post( $post->ID );
		?>
		<p><label><input type="checkbox" name="ogmake_disable" value="1" <?php checked( $disable ); ?> /> <?php esc_html_e( 'Do not add an ogmake image', 'ogmake-og-images' ); ?></label></p>
		<p><label for="ogmake_template"><?php esc_html_e( 'Template (blank = default)', 'ogmake-og-images' ); ?></label>
			<input type="text" class="widefat" id="ogmake_template" name="ogmake_template" value="<?php echo esc_attr( $template ); ?>" /></p>
		<p><label for="ogmake_title"><?php esc_html_e( 'Image title (blank = post title)', 'ogmake-og-images' ); ?></label>
			<input type="text" class="widefat" id="ogmake_title" name="ogmake_title" value="<?php echo esc_attr( $title ); ?>" /></p>
		<?php if ( '' !== $preview ) : ?>
			<p><?php esc_html_e( 'Preview (as of the last save):', 'ogmake-og-images' ); ?></p>
			<img src="<?php echo esc_url( $preview ); ?>" alt="" style="max-width:100%;height:auto;" loading="lazy" />
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Nothing to preview: opted out or not configured (Settings, ogmake).', 'ogmake-og-images' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save overrides.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST['ogmake_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ogmake_nonce'] ) ), 'ogmake_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$map = array(
			'_ogmake_template' => isset( $_POST['ogmake_template'] ) ? sanitize_key( wp_unslash( $_POST['ogmake_template'] ) ) : '',
			'_ogmake_title'    => isset( $_POST['ogmake_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ogmake_title'] ) ) : '',
			'_ogmake_disable'  => empty( $_POST['ogmake_disable'] ) ? '' : '1',
		);
		foreach ( $map as $key => $value ) {
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
}
