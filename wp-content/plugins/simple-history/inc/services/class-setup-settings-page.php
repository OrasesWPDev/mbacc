<?php

namespace Simple_History\Services;

use Simple_History\Simple_History;
use Simple_History\Helpers;

/**
 * Setup settings page.
 */
class Setup_Settings_Page extends Service {
	/**
	 * @inheritdoc
	 */
	public function loaded() {
		add_action( 'admin_menu', [ $this, 'add_admin_pages' ] );
		add_action( 'after_setup_theme', [ $this, 'add_default_settings_tabs' ] );
		add_action( 'admin_menu', [ $this, 'add_settings' ], 10 );
	}

	/**
	 * Adds default tabs to settings
	 */
	public function add_default_settings_tabs() {
		// Add sub tabs.
		$this->simple_history->register_settings_tab(
			[
				'parent_slug' => 'settings',
				'slug' => 'general_settings_subtab_general',
				'name' => __( 'General', 'simple-history' ),
				'order' => 100,
				'function' => [ $this, 'settings_output_general' ],
			]
		);
	}

	/**
	 * Output for the general settings tab.
	 */
	public function settings_output_general() {
		include SIMPLE_HISTORY_PATH . 'templates/settings-general.php';
	}

	/**
	 * Add options/settings menu page for settings.
	 */
	public function add_admin_pages() {
		// Add a settings page.
		$show_settings_page = true;
		$show_settings_page = apply_filters( 'simple_history_show_settings_page', $show_settings_page );
		$show_settings_page = apply_filters( 'simple_history/show_settings_page', $show_settings_page );

		// Can't show settings page if user can't view main menu item.
		if ( ! Helpers::setting_show_as_menu_page() ) {
			return;
		}

		if ( $show_settings_page ) {
			// Old location: placed at WP Admin › Settings › Simple History.
			add_options_page(
				__( 'Simple History Settings', 'simple-history' ),
				_x( 'Simple History', 'Options page menu title', 'simple-history' ),
				Helpers::get_view_settings_capability(),
				Simple_History::SETTINGS_MENU_SLUG,
				array( $this, 'settings_page_output_redirect' )
			);

			// New location: placed at WP Admin › Simple History › Settings.
			add_submenu_page(
				Simple_History::MENU_PAGE_SLUG,
				_x( 'Simple History Settings', 'settings title name', 'simple-history' ),
				_x( 'Settings', 'settings menu name', 'simple-history' ),
				Helpers::get_view_settings_capability(),
				'simple_history_settings_page',
				array( $this, 'settings_page_output' )
			);

		}
	}

	/**
	 * Redirects old settings page to new settings page.
	 */
	public function settings_page_output_redirect() {
		$redirect_to_url = add_query_arg(
			[
				'page' => 'simple_history_settings_page',
				'simple_history_redirected_from_settings_menu' => '1',
			],
			admin_url( 'admin.php' )
		);

		if ( headers_sent() ) {
			// Decode the URL to prevent double encoding of ampersands.
			$js_url = html_entity_decode( esc_url( $redirect_to_url ) );
			?>
			<script>
				window.location = <?php echo wp_json_encode( $js_url ); ?>;
			</script>
			<?php
		} else {
			wp_redirect( $redirect_to_url );
			exit;
		}
	}

	/**
	 * Add setting sections and settings for the settings page.
	 *
	 * Also save some settings before outputting them.
	 */
	public function add_settings() {
		$this->clear_log_from_url_request();

		$settings_section_general_id = $this->simple_history::SETTINGS_SECTION_GENERAL_ID;
		$settings_menu_slug = $this->simple_history::SETTINGS_MENU_SLUG;
		$settings_general_option_group = $this->simple_history::SETTINGS_GENERAL_OPTION_GROUP;

		Helpers::add_settings_section(
			$settings_section_general_id,
			[ __( 'General', 'simple-history' ), 'tune' ],
			[ $this, 'settings_section_output' ],
			$settings_menu_slug // Same slug as for options menu page.
		);

		// Checkboxes for where to show simple history.
		register_setting(
			$settings_general_option_group,
			'simple_history_show_on_dashboard',
			array(
				'sanitize_callback' => array(
					Helpers::class,
					'sanitize_checkbox_input',
				),
			)
		);

		// Setting for showing as page under dashboard.
		register_setting(
			$settings_general_option_group,
			'simple_history_show_as_page',
			array(
				'sanitize_callback' => array(
					Helpers::class,
					'sanitize_checkbox_input',
				),
			)
		);

		// Setting for showing in admin bar.
		register_setting(
			$settings_general_option_group,
			'simple_history_show_in_admin_bar',
			array(
				'sanitize_callback' => array(
					Helpers::class,
					'sanitize_checkbox_input',
				),
			)
		);

		// Setting for menu page location.
		register_setting(
			$settings_general_option_group,
			'simple_history_menu_page_location',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		// Output for where to show history, in dashboard, admin bar.
		add_settings_field(
			'simple_history_show_where',
			Helpers::get_settings_field_title_output( __( 'Show history', 'simple-history' ), 'visibility' ),
			array( $this, 'settings_field_where_to_show' ),
			$settings_menu_slug,
			$settings_section_general_id
		);

		add_settings_field(
			'simple_history_menu_page_location',
			Helpers::get_settings_field_title_output( __( 'Menu page location', 'simple-history' ), 'overview' ),
			array( $this, 'settings_field_menu_page_location' ),
			$settings_menu_slug,
			$settings_section_general_id
		);

		// Output for number if items to show on the history page.
		add_settings_field(
			'simple_history_number_of_items',
			Helpers::get_settings_field_title_output( __( 'Items per page', 'simple-history' ), 'filter_list' ),
			array( $this, 'settings_field_number_of_items' ),
			$settings_menu_slug,
			$settings_section_general_id
		);

		// Nonces for number of items inputs.
		register_setting( $settings_general_option_group, 'simple_history_pager_size' );

		// Nonces for number of items inputs.
		register_setting( $settings_general_option_group, 'simple_history_pager_size_dashboard' );

		// Link/button to clear log.
		if ( Helpers::user_can_clear_log() ) {
			add_settings_field(
				'simple_history_clear_log',
				Helpers::get_settings_field_title_output( __( 'Clear log', 'simple-history' ), 'auto-delete' ),
				[ $this, 'settings_field_clear_log' ],
				$settings_menu_slug,
				$settings_section_general_id
			);
		}
	}

	/**
	 * Content for general section intro.
	 */
	public function settings_section_output() {
		/**
		 * Fires before the general settings section output.
		 * Can be used to output content in the general settings section.
		 */
		do_action( 'simple_history/settings_page/general_section_output' );
	}

	/**
	 * Settings field output for menu page location
	 */
	public function settings_field_menu_page_location() {
		$location = Helpers::setting_menu_page_location();
		$option_slug = 'simple_history_menu_page_location';
		?>

		<fieldset>
			<label>
				<input 
					type="radio"
					name="<?php echo esc_attr( $option_slug ); ?>"
					value="top"
					<?php checked( $location === 'top' ); ?>
				/>
				<?php esc_html_e( 'Top of menu', 'simple-history' ); ?>
			</label>

			<br />

			<label>
				<input 
					type="radio"
					name="<?php echo esc_attr( $option_slug ); ?>"
					value="bottom"
					<?php checked( $location === 'bottom' ); ?>
				/>
				<?php esc_html_e( 'Bottom of menu', 'simple-history' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Settings field for where to show the log, page or dashboard
	 */
	public function settings_field_where_to_show() {
		$show_on_dashboard = Helpers::setting_show_on_dashboard();
		$show_in_admin_bar = Helpers::setting_show_in_admin_bar();
		$show_as_page_below_dashboard = Helpers::setting_show_as_page();
		?>

		<input <?php checked( $show_on_dashboard ); ?> type="checkbox" value="1" name="simple_history_show_on_dashboard" id="simple_history_show_on_dashboard" class="simple_history_show_on_dashboard" />
		<label for="simple_history_show_on_dashboard">
			<?php esc_html_e( 'on the dashboard', 'simple-history' ); ?>
		</label>

		<br />

		<input <?php checked( $show_as_page_below_dashboard ); ?> type="checkbox" value="1" name="simple_history_show_as_page" id="simple_history_show_as_page" class="simple_history_show_as_page" />
		<label for="simple_history_show_as_page">
			<?php esc_html_e( 'as a page under the dashboard menu', 'simple-history' ); ?>
		</label>
		
		<br />

		<input <?php checked( $show_in_admin_bar ); ?> type="checkbox" value="1" name="simple_history_show_in_admin_bar" id="simple_history_show_in_admin_bar" class="simple_history_show_in_admin_bar" />
		<label for="simple_history_show_in_admin_bar">
			<?php esc_html_e( 'in the admin bar', 'simple-history' ); ?>
		</label>

		<?php
	}

	/**
	 * Settings field for how many rows/items to show in log on the log page
	 */
	public function settings_field_number_of_items() {
		$this->settings_field_number_of_items_on_log_page();
		echo '<br /><br />';
		$this->settings_field_number_of_items_dashboard();
	}

	/**
	 * Settings field for how many rows/items to show in log on the log page
	 */
	private function settings_field_number_of_items_on_log_page() {
		$current_pager_size = Helpers::get_pager_size();
		$pager_size_default_values = array( 5, 10, 15, 20, 25, 30, 40, 50, 75, 100 );

		echo '<p>' . esc_html__( 'Number of items per page on the log page', 'simple-history' ) . '</p>';

		// If number of items is controlled via filter then return early.
		if ( has_filter( 'simple_history/pager_size' ) ) {
			printf(
				'<input type="text" readonly value="%1$s" />',
				esc_html( $current_pager_size ),
			);

			return;
		}

		?>
		<select name="simple_history_pager_size">
			<?php
			foreach ( $pager_size_default_values as $one_value ) {
				$selected = selected( $current_pager_size, $one_value, false );

				printf(
					'<option %1$s value="%2$s">%2$s</option>',
					esc_html( $selected ),
					esc_html( $one_value )
				);
			}

			// If current pager size is not among array values then manually output selected value here.
			// This can happen if user has set a value that is not in the array.
			if ( ! in_array( $current_pager_size, $pager_size_default_values, true ) ) {
				printf(
					'<option selected="selected" value="%1$s">%1$s</option>',
					esc_html( $current_pager_size )
				);
			}
			?>
		</select>

		<?php
	}

	/**
	 * Settings field for how many rows/items to show in log on the dashboard
	 */
	private function settings_field_number_of_items_dashboard() {
		$current_pager_size = Helpers::get_pager_size_dashboard();
		$pager_size_default_values = array( 5, 10, 15, 20, 25, 30, 40, 50, 75, 100 );

		echo '<p>' . esc_html__( 'Number of items per page on the dashboard', 'simple-history' ) . '</p>';

		// If number of items is controlled via filter then return early.
		if ( has_filter( 'simple_history_pager_size_dashboard' ) || has_filter( 'simple_history/dashboard_pager_size' ) ) {
			printf(
				'<input type="text" readonly value="%1$s" />',
				esc_html( $current_pager_size ),
			);

			return;
		}

		?>
		<select name="simple_history_pager_size_dashboard">
			<?php
			foreach ( $pager_size_default_values as $one_value ) {
				$selected = selected( $current_pager_size, $one_value, false );

				printf(
					'<option %1$s value="%2$s">%2$s</option>',
					esc_html( $selected ),
					esc_html( $one_value )
				);
			}

			// If current pager size is not among array values then manually output selected value here.
			// This can happen if user has set a value that is not in the array.
			if ( ! in_array( $current_pager_size, $pager_size_default_values, true ) ) {
				printf(
					'<option selected="selected" value="%1$s">%1$s</option>',
					esc_html( $current_pager_size )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Settings section to clear database.
	 */
	public function settings_field_clear_log() {
		// Get base URL to current page.
		// Will be like "/wordpress/wp-admin/admin.php?page=simple_history_admin_menu_page&".
		$clear_link = add_query_arg( '', '' );

		// Append nonce to URL.
		$clear_link = wp_nonce_url( $clear_link, 'simple_history_clear_log', 'simple_history_clear_log_nonce' );

		$clear_days = Helpers::get_clear_history_interval();

		echo '<p>';

		if ( $clear_days > 0 ) {
			printf(
				// translators: %1$s is number of days.
				esc_html__( 'Items in the database are automatically removed after %1$s days.', 'simple-history' ),
				esc_html( $clear_days )
			);
			echo '<br>';
		} else {
			esc_html_e( 'Items in the database are kept forever.', 'simple-history' );
		}

		echo '</p>';

		// View Premium add-on information, if not already installed.
		if ( Helpers::show_promo_boxes() ) {
			?>
			<p>
				<a href="https://simple-history.com/premium/?utm_source=wpadmin&utm_content=purge-interval" target="_blank" class="sh-ExternalLink">
					<?php esc_html_e( 'Upgrade to Simple History Premium to set this to any number of days.', 'simple-history' ); ?>
				</a>
			</p>
			<?php
		}

		printf(
			'<p><a class="button js-SimpleHistory-Settings-ClearLog" href="%2$s">%1$s</a></p>',
			esc_html__( 'Clear log now', 'simple-history' ),
			esc_url( $clear_link )
		);
	}


	/**
	 * Output HTML for the settings page.
	 * Called from add_options_page.
	 */
	public function settings_page_output() {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Admin_Pages::header_output(
			self::get_main_nav_html(),
			self::get_subnav_html()
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		// TODO: in the above more than the header is outputted. Should be refactored to only output header.
	}

	/**
	 * Get HTML for the sub navigation.
	 *
	 * @return string
	 */
	public static function get_subnav_html() {
		ob_start();

		$simple_history = Simple_History::get_instance();

		$arr_settings_tabs = $simple_history->get_settings_tabs();
		$arr_settings_tabs_sub = $simple_history->get_settings_tabs( 'sub' );

		// Begin subnav.
		$sub_tab_found = false;
		$active_sub_tab = sanitize_text_field( wp_unslash( $_GET['selected-sub-tab'] ?? '' ) );
		$active_tab = self::get_active_tab_slug();

		// Get sub tabs for currently active tab.
		$subtabs_for_active_tab = wp_filter_object_list(
			$arr_settings_tabs_sub,
			array(
				'parent_slug' => $active_tab,
			)
		);

		// Re-index array, so 0 is first sub tab.
		$subtabs_for_active_tab = array_values( $subtabs_for_active_tab );

		// If sub tabs are found but no active sub tab, then
		// make first sub tab automatically active.
		if ( count( $subtabs_for_active_tab ) > 0 && empty( $active_sub_tab ) ) {
			$active_sub_tab = $subtabs_for_active_tab[0]['slug'];
		}

		if ( count( $subtabs_for_active_tab ) > 0 ) {

			// Output subnav tabs if number of tabs are more than 1.
			// If only one tab then no need to output subnav.
			if ( count( $subtabs_for_active_tab ) > 1 ) {
				?>
				<nav class="sh-SettingsTabs">
					<ul class="sh-SettingsTabs-tabs">
						<?php
						foreach ( $subtabs_for_active_tab as $one_sub_tab ) {
							$is_active = $active_sub_tab === $one_sub_tab['slug'];
							$is_active_class = $is_active ? 'is-active' : '';
							$plug_settings_tab_url = Helpers::get_settings_page_sub_tab_url( $one_sub_tab['slug'] );

							?>
							<li class="sh-SettingsTabs-tab">
								<a class="sh-SettingsTabs-link <?php echo esc_attr( $is_active_class ); ?>" href="<?php echo esc_url( $plug_settings_tab_url ); ?>">
									<?php echo esc_html( $one_sub_tab['name'] ); ?>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
				</nav>
				<?php
			}

			// Get the active sub tab and call its output function.
			$active_sub_tabs = wp_filter_object_list(
				$arr_settings_tabs_sub,
				array(
					'parent_slug' => $active_tab,
					'slug' => $active_sub_tab,
				)
			);

			$active_sub_tab = reset( $active_sub_tabs );
			$sub_tab_found = is_array( $active_sub_tab );

			if ( $sub_tab_found ) {
				if ( is_callable( $active_sub_tab['function'] ) ) {
					call_user_func( $active_sub_tab['function'] );
				} else {
					echo esc_html(
						sprintf(
							/* translators: %s is the slug of the sub tab */
							__( 'Function not found for sub tab "%1$s".', 'simple-history' ),
							$active_sub_tab['slug']
						)
					);
				}
			}
		}

		// Output contents for selected main tab,
		// if no sub tab outputted content.
		if ( ! $sub_tab_found ) {
			$arr_active_tab = wp_filter_object_list(
				$arr_settings_tabs,
				array(
					'slug' => $active_tab,
				)
			);
			$arr_active_tab = current( $arr_active_tab );

			// We must have found an active tab and it must have a callable function.
			if ( ! $arr_active_tab || ! is_callable( $arr_active_tab['function'] ) ) {
				wp_die( esc_html__( 'No valid callback found', 'simple-history' ) );
			}

			$args = array(
				'arr_active_tab' => $arr_active_tab,
			);

			call_user_func_array( $arr_active_tab['function'], array_values( $args ) );
		}

		return ob_get_clean();
	}

	/**
	 * Get the slug of the active tab.
	 *
	 * @return string
	 */
	public static function get_active_tab_slug() {
		return sanitize_text_field( wp_unslash( $_GET['selected-tab'] ?? 'settings' ) );
	}

	/**
	 * Get HTML for the main navigation.
	 *
	 * @return string
	 */
	public static function get_main_nav_html() {
		ob_start();

		$simple_history = Simple_History::get_instance();

		$arr_settings_tabs = $simple_history->get_settings_tabs();

		?>
		<nav class="sh-PageNav">
			<?php
			$active_tab = self::get_active_tab_slug();

			foreach ( $arr_settings_tabs as $one_tab ) {
				$tab_slug = $one_tab['slug'];

				$icon_html = '';
				if ( ! is_null( $one_tab['icon'] ?? null ) ) {
					$icon_html = sprintf(
						'<span class="sh-PageNav-icon sh-Icon--%1$s"></span>',
						esc_attr( $one_tab['icon'] )
					);
				}

				$icon_html_allowed_html = [
					'span' => [
						'class' => [],
					],
				];

				printf(
					'<a href="%3$s" class="sh-PageNav-tab %4$s">%5$s%1$s</a>',
					esc_html( $one_tab['name'] ), // 1
					esc_html( $tab_slug ), // 2
					esc_url( Helpers::get_settings_page_tab_url( $tab_slug ) ), // 3
					$active_tab == $tab_slug ? 'is-active' : '', // 4
					wp_kses( $icon_html, $icon_html_allowed_html ) // 5
				);
			}
			?>
		</nav>
		<?php

		return ob_get_clean();
	}

	/**
	 * Detect clear log query arg and clear log if it is set and valid.
	 */
	public function clear_log_from_url_request() {
		// Clear the log if clear button was clicked in settings
		// and redirect user to show message.
		if (
			isset( $_GET['simple_history_clear_log_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['simple_history_clear_log_nonce'] ) ), 'simple_history_clear_log' )
		) {
			if ( Helpers::user_can_clear_log() ) {
				$num_rows_deleted = Helpers::clear_log();

				/**
				 * Fires after the log has been cleared using
				 * the "Clear log now" button on the settings page.
				 *
				 * @param int $num_rows_deleted Number of rows deleted.
				 */
				do_action( 'simple_history/settings/log_cleared', $num_rows_deleted );
			}

			$msg = __( 'Cleared database', 'simple-history' );

			add_settings_error(
				'simple_history_settings_clear_log',
				'simple_history_settings_clear_log',
				$msg,
				'updated'
			);

			set_transient( 'settings_errors', get_settings_errors(), 30 );

			$goback = esc_url_raw( add_query_arg( 'settings-updated', 'true', wp_get_referer() ) );
			wp_redirect( $goback );
			exit();
		}
	}
}
