<?php
defined( 'ABSPATH' ) || exit;

class Wpcvd_Backend {
	protected static $instance = null;
	protected static $settings = [];

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		self::$settings = (array) get_option( 'wpcvd_settings', [] );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'add_duplicate_btn' ], 10, 3 );

		// Settings
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

		// AJAX
		add_action( 'wp_ajax_wpcvd_duplicate', [ $this, 'ajax_duplicate' ] );
	}

	public static function get_settings() {
		return apply_filters( 'wpcvd_get_settings', self::$settings );
	}

	public static function get_setting( $name, $default = false ) {
		if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
			$setting = self::$settings[ $name ];
		} else {
			$setting = get_option( 'wpcvd_' . $name, $default );
		}

		return apply_filters( 'wpcvd_get_setting', $setting, $name, $default );
	}

	function register_settings() {
		// settings
		register_setting( 'wpcvd_settings', 'wpcvd_settings' );
	}

	function admin_menu() {
		add_submenu_page( 'wpclever', esc_html__( 'WPC Variation Duplicator', 'wpc-variation-duplicator' ), esc_html__( 'Variation Duplicator', 'wpc-variation-duplicator' ), 'manage_options', 'wpclever-wpcvd', [
			$this,
			'admin_menu_content'
		] );
	}

	function admin_menu_content() {
		add_thickbox();
		$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
		?>
        <div class="wpclever_settings_page wrap">
            <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Variation Duplicator', 'wpc-variation-duplicator' ) . ' ' . esc_html( WPCVD_VERSION ); ?></h1>
            <div class="wpclever_settings_page_desc about-text">
                <p>
					<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-variation-duplicator' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                    <br/>
                    <a href="<?php echo esc_url( WPCVD_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'wpc-variation-duplicator' ); ?></a> |
                    <a href="<?php echo esc_url( WPCVD_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'wpc-variation-duplicator' ); ?></a> |
                    <a href="<?php echo esc_url( WPCVD_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'wpc-variation-duplicator' ); ?></a>
                </p>
            </div>
			<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Settings updated.', 'wpc-variation-duplicator' ); ?></p>
                </div>
			<?php } ?>
            <div class="wpclever_settings_page_nav">
                <h2 class="nav-tab-wrapper">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcvd&tab=settings' ) ); ?>" class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
						<?php esc_html_e( 'Settings', 'wpc-variation-duplicator' ); ?>
                    </a> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
						<?php esc_html_e( 'Essential Kit', 'wpc-variation-duplicator' ); ?>
                    </a>
                </h2>
            </div>
            <div class="wpclever_settings_page_content">
				<?php if ( $active_tab === 'settings' ) {
					$copies = self::get_setting( 'copies', 'default' );
					?>
                    <form method="post" action="options.php">
                        <table class="form-table">
                            <tr class="heading">
                                <th colspan="2">
									<?php esc_html_e( 'General', 'wpc-variation-duplicator' ); ?>
                                </th>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Duplicated into how many copies', 'wpc-variation-duplicator' ); ?></th>
                                <td>
                                    <label> <select name="wpcvd_settings[copies]">
                                            <option value="default" <?php selected( $copies, 'default' ); ?>><?php esc_html_e( 'Default (1 only)', 'wpc-variation-duplicator' ); ?></option>
                                            <option value="custom" <?php selected( $copies, 'custom' ); ?>><?php esc_html_e( 'Custom', 'wpc-variation-duplicator' ); ?></option>
                                        </select> </label>
                                    <span class="description"><?php esc_html_e( 'Specify how many copies to be made by pressing the Duplicate button. Choose Custom to allow users to enter a number indicating how many copies to be made every time they press the Duplicate button.', 'wpc-variation-duplicator' ); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Custom fields', 'wpc-variation-duplicator' ); ?></th>
                                <td>
                                    <label>
                                        <textarea name="wpcvd_settings[custom_fields]" rows="10" cols="50" class="large-text"><?php echo self::get_setting( 'custom_fields' ); ?></textarea>
                                    </label>
                                    <span class="description"><?php esc_html_e( 'Some other plugins add additional fields for variations. If you also want to duplicate these custom fields, please fill in the custom fields\' key and separate them by a new line.', 'wpc-variation-duplicator' ); ?></span>
                                </td>
                            </tr>
                            <tr class="submit">
                                <th colspan="2">
									<?php settings_fields( 'wpcvd_settings' ); ?><?php submit_button(); ?>
                                </th>
                            </tr>
                        </table>
                    </form>
				<?php } ?>
            </div><!-- /.wpclever_settings_page_content -->
            <div class="wpclever_settings_page_suggestion">
                <div class="wpclever_settings_page_suggestion_label">
                    <span class="dashicons dashicons-yes-alt"></span> Suggestion
                </div>
                <div class="wpclever_settings_page_suggestion_content">
                    <div>
                        To display custom engaging real-time messages on any wished positions, please install
                        <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart Messages</a> plugin. It's free!
                    </div>
                    <div>
                        Wanna save your precious time working on variations? Try our brand-new free plugin
                        <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC Variation Bulk Editor</a> and
                        <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC Variation Duplicator</a>.
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	function add_duplicate_btn( $loop, $variation_data, $variation ) {
		echo '<a href="javascript:void(0)" class="wpcvd-btn hint--left" data-id="' . esc_attr( $variation->ID ?? 0 ) . '" aria-label="' . esc_attr__( 'Click to duplicate', 'wpc-variation-duplicator' ) . '">' . esc_html__( 'Duplicate', 'wpc-variation-duplicator' ) . '</a>';
	}

	function ajax_duplicate() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcvd_duplicate' ) ) {
			die( 'Permissions check failed!' );
		}

		global $post;

		$loop                 = absint( $_POST['loop'] );
		$copies               = absint( $_POST['copies'] );
		$product_id           = absint( $_POST['post_id'] );
		$old_variation_id     = absint( $_POST['variation_id'] );
		$post                 = get_post( $product_id );
		$product_object       = wc_get_product_object( 'variable', $product_id );
		$old_variation_object = wc_get_product_object( 'variation', $old_variation_id );
		$product_sku          = $product_object->get_sku();
		$old_variation_sku    = $old_variation_object->get_sku();

		if ( $copies ) {
			for ( $i = 0; $i < $copies; $i ++ ) {
				// duplicate a variation
				$variation_object = wc_get_product_object( 'variation', $old_variation_id );

				if ( wc_product_sku_enabled() && ! empty( $old_variation_sku ) && ( $old_variation_sku !== $product_sku ) ) {
					$variation_object->set_props( [ 'id' => 0, 'sku' => $old_variation_sku . '-' . ( $i + 1 ) ] );
				} else {
					$variation_object->set_props( [ 'id' => 0 ] );
				}

				$variation_object->set_parent_id( $product_id );
				$variation_id = $variation_object->save();

				// action hook
				do_action( 'wpcvd_duplicated', $old_variation_id, $variation_id );

				// custom fields
				$custom_fields = self::get_setting( 'custom_fields' );

				if ( ! empty( $custom_fields ) ) {
					$custom_fields_arr = array_map( 'trim', explode( "\n", $custom_fields ) );
				}

				if ( ! empty( $custom_fields_arr ) ) {
					foreach ( $custom_fields_arr as $custom_field ) {
						if ( ! empty( $custom_field ) && ( $custom_field_value = get_post_meta( $old_variation_id, $custom_field, true ) ) ) {
							update_post_meta( $variation_id, $custom_field, $custom_field_value );
						}
					}
				}

				$variation      = get_post( $variation_id );
				$variation_data = array_merge( get_post_custom( $variation_id ), wc_get_product_variation_attributes( $variation_id ) );
				include dirname( WC_PLUGIN_FILE ) . '/includes/admin/meta-boxes/views/html-variation-admin.php';
			}
		}

		wp_die();
	}

	public function admin_scripts() {
		if ( 'product' === get_post_type() ) {
			wp_enqueue_style( 'hint', WPCVD_URI . 'assets/css/hint.css' );
			wp_enqueue_style( 'wpcvd-backend', WPCVD_URI . 'assets/css/backend.css', [], WPCVD_VERSION );
			wp_enqueue_script( 'wpcvd-backend', WPCVD_URI . 'assets/js/backend.js', [ 'jquery' ], WPCVD_VERSION, true );
			wp_localize_script( 'wpcvd-backend', 'wpcvd_vars', [
					'nonce'             => wp_create_nonce( 'wpcvd_duplicate' ),
					'copies'            => self::get_setting( 'copies', 'default' ),
					'copies_text'       => esc_attr__( 'How many copies to be made?', 'wpc-variation-duplicator' ),
					'ready_duplicate'   => esc_attr__( 'Click to duplicate', 'wpc-variation-duplicator' ),
					'save_before'       => esc_attr__( 'Save before duplicate', 'wpc-variation-duplicator' ),
					'duplicated_notice' => /* translators: variation id */ esc_attr__( 'Duplicated from %s', 'wpc-variation-duplicator' ),
				]
			);
		}
	}

	function action_links( $links, $file ) {
		static $plugin;

		if ( ! isset( $plugin ) ) {
			$plugin = plugin_basename( WPCVD_FILE );
		}

		if ( $plugin === $file ) {
			$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpcvd&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-variation-duplicator' ) . '</a>';
			array_unshift( $links, $settings );
		}

		return (array) $links;
	}

	function row_meta( $links, $file ) {
		static $plugin;

		if ( ! isset( $plugin ) ) {
			$plugin = plugin_basename( WPCVD_FILE );
		}

		if ( $plugin === $file ) {
			$row_meta = [
				'support' => '<a href="' . esc_url( WPCVD_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-variation-duplicator' ) . '</a>',
			];

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}
}

Wpcvd_Backend::instance();
