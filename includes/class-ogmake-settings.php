<?php
/**
 * Settings page and option access.
 *
 * @package Ogmake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings.
 */
class Ogmake_Settings {

	const OPTION = 'ogmake_settings';

	/**
	 * Default values.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'mode'           => 'public', // 'public' (unsigned /p) or 'signed' (/i).
			'base_url'       => 'https://ogmake.com',
			'key_id'         => '',
			'signing_secret' => '',
			'template'       => 'basic',
			'background'     => '',
			'site_name'      => '',
			'override_seo'   => 0,
			'use_featured'   => 0,
			'post_types'     => array( 'post', 'page' ),
		);
	}

	/**
	 * Current settings merged over defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
	}

	/**
	 * Add the settings page.
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_options_page(
			__( 'ogmake OG Images', 'ogmake-og-images' ),
			__( 'ogmake', 'ogmake-og-images' ),
			'manage_options',
			'ogmake',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register the option.
	 *
	 * @return void
	 */
	public static function register_setting() {
		register_setting(
			'ogmake',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize submitted settings. A blank secret keeps the stored one, so the
	 * secret is never echoed back into the form.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$old   = self::get();
		$out   = self::defaults();

		$out['mode']           = ( isset( $input['mode'] ) && 'signed' === $input['mode'] ) ? 'signed' : 'public';
		$base                  = isset( $input['base_url'] ) ? esc_url_raw( trim( (string) $input['base_url'] ) ) : '';
		$out['base_url']       = '' !== $base ? untrailingslashit( $base ) : $out['base_url'];
		$out['key_id']         = isset( $input['key_id'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $input['key_id'] ) : '';
		$secret                = isset( $input['signing_secret'] ) ? trim( (string) $input['signing_secret'] ) : '';
		$out['signing_secret'] = '' !== $secret ? preg_replace( '/[^A-Za-z0-9+\/=]/', '', $secret ) : $old['signing_secret'];
		$out['template']       = isset( $input['template'] ) ? sanitize_key( (string) $input['template'] ) : 'basic';
		$out['template']       = '' !== $out['template'] ? $out['template'] : 'basic';
		$out['background']     = isset( $input['background'] ) ? sanitize_key( (string) $input['background'] ) : '';
		$out['site_name']      = isset( $input['site_name'] ) ? sanitize_text_field( (string) $input['site_name'] ) : '';
		$out['override_seo']   = empty( $input['override_seo'] ) ? 0 : 1;
		$out['use_featured']   = empty( $input['use_featured'] ) ? 0 : 1;
		$types                 = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : array();
		$out['post_types']     = array_values( array_map( 'sanitize_key', $types ) );

		ogmake_bump_cache_generation();
		return $out;
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s       = self::get();
		$name    = self::OPTION;
		$has_sec = '' !== $s['signing_secret'];
		$types   = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ogmake OG Images', 'ogmake-og-images' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'ogmake' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mode', 'ogmake-og-images' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[mode]" value="public" <?php checked( $s['mode'], 'public' ); ?> /> <?php esc_html_e( 'Public URL (key id only, no secret)', 'ogmake-og-images' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[mode]" value="signed" <?php checked( $s['mode'], 'signed' ); ?> /> <?php esc_html_e( 'API key (signed URLs, secret stays on the server)', 'ogmake-og-images' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ogmake-base"><?php esc_html_e( 'API base URL', 'ogmake-og-images' ); ?></label></th>
						<td><input id="ogmake-base" class="regular-text" type="url" name="<?php echo esc_attr( $name ); ?>[base_url]" value="<?php echo esc_attr( $s['base_url'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ogmake-key"><?php esc_html_e( 'Key id', 'ogmake-og-images' ); ?></label></th>
						<td><input id="ogmake-key" class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[key_id]" value="<?php echo esc_attr( $s['key_id'] ); ?>" placeholder="k_..." /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ogmake-secret"><?php esc_html_e( 'Signing secret', 'ogmake-og-images' ); ?></label></th>
						<td>
							<input id="ogmake-secret" class="regular-text" type="password" autocomplete="off" name="<?php echo esc_attr( $name ); ?>[signing_secret]" value="" placeholder="<?php echo $has_sec ? esc_attr__( 'Saved. Leave blank to keep.', 'ogmake-og-images' ) : ''; ?>" />
							<p class="description"><?php esc_html_e( 'Only needed in API key mode. Never printed on the site.', 'ogmake-og-images' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ogmake-template"><?php esc_html_e( 'Default template', 'ogmake-og-images' ); ?></label></th>
						<td>
							<input id="ogmake-template" class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[template]" value="<?php echo esc_attr( $s['template'] ); ?>" />
							<p class="description"><?php esc_html_e( 'For example basic or blog. In public mode it must be on the key\'s allow-list.', 'ogmake-og-images' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ogmake-bg"><?php esc_html_e( 'Background id', 'ogmake-og-images' ); ?></label></th>
						<td>
							<input id="ogmake-bg" class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[background]" value="<?php echo esc_attr( $s['background'] ); ?>" placeholder="bg-01" />
							<p class="description"><?php esc_html_e( 'Optional curated background. Colors, fonts and your logo are set in your ogmake dashboard.', 'ogmake-og-images' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ogmake-site"><?php esc_html_e( 'Site name', 'ogmake-og-images' ); ?></label></th>
						<td><input id="ogmake-site" class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[site_name]" value="<?php echo esc_attr( $s['site_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post types', 'ogmake-og-images' ); ?></th>
						<td>
							<?php foreach ( $types as $slug => $obj ) : ?>
								<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[post_types][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $s['post_types'], true ) ); ?> /> <?php echo esc_html( $obj->labels->name ); ?></label><br />
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Featured image', 'ogmake-og-images' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[use_featured]" value="1" <?php checked( 1, (int) $s['use_featured'] ); ?> /> <?php esc_html_e( 'Keep the post\'s featured image as og:image when it has one', 'ogmake-og-images' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'SEO plugins', 'ogmake-og-images' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[override_seo]" value="1" <?php checked( 1, (int) $s['override_seo'] ); ?> /> <?php esc_html_e( 'Override the og:image set by Yoast SEO or Rank Math (off: they win when they have one)', 'ogmake-og-images' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
