<?php
/*
 *	Add field to user profile page
 */
function admin_search_user_option(  $user  ) {

	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

?>
<tr>
	<th><label for="hide_in_admin_search"><?php esc_html_e( 'Admin Search', 'admin-search' ); ?></label></th>
	<td>
		<label for="hide_in_admin_search">
			<input name="hide_in_admin_search" type="checkbox" id="hide_in_admin_search" value="1"<?php echo get_the_author_meta( 'hide_in_admin_search', $user -> ID ) ? ' checked' : ''; ?>>
			<?php esc_html_e( 'Do not include profile in Admin Search results', 'admin-search' ); ?>
		</label>
	</td>
</tr>
<?php

}

add_action( 'personal_options', 'admin_search_user_option' );


function admin_search_save_user_option(  $user_id  ) {

	if ( ! current_user_can( 'edit_user', $user_id ) ) { 
		return false; 
	}

	if ( empty( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'update-user_' . $user_id ) ) {
		return;
	}

	if ( isset( $_POST[ 'hide_in_admin_search' ] ) ) {
		update_user_meta( $user_id, 'hide_in_admin_search', $_POST[ 'hide_in_admin_search' ] );
	}

}

add_action( 'personal_options_update', 'admin_search_save_user_option' );
add_action( 'edit_user_profile_update', 'admin_search_save_user_option' );



/*
 *	Add plugin settings page
 */
function admin_search_add_settings_page() {

	add_options_page(
		__( 'Admin Search Settings', 'admin-search' ),
		__( 'Admin Search', 'admin-search' ),
		'manage_options',
		'admin-search',
		'admin_search_render_plugin_settings_page'
	);

	add_filter( 'admin_footer_text', function( $text ) {
		if ( get_current_screen() -> id === 'settings_page_admin-search' ) {
			return '<a target="_blank" href="https://wordpress.org/support/plugin/admin-search/#new-post">' . esc_attr__( 'Get support', 'admin-search' ) . '</a> | ' . str_replace(
				array(
					'[a rate]',
					'[a wp]',
					'[a donate]',
					'[/a]'
				),
				array(
					'<a target="_blank" href="https://wordpress.org/support/plugin/admin-search/reviews/#new-post">',
					'<a target="_blank" href="https://wordpress.org/plugin/admin-search/">',
					'<a target="_blank" href="https://www.buymeacoffee.com/andrewstichbury">',
					'</a>'
				),
				__( '[a rate]Leave a review[/a] on [a wp]WordPress.org[/a] or [a donate]donate[/a] to support the plugin.', 'admin-search' )
			);
		}

		return $text;
	} );

	add_filter( 'update_footer', function( $text ) {
		if ( get_current_screen() -> id === 'settings_page_admin-search' ) {

			/* translators: %s: the version of Admin Search currently installed */
			return '<a target="_blank" href="https://translate.wordpress.org/projects/wp-plugins/admin-search/"><span class="dashicons dashicons-translation"></span></a> ' . sprintf( __( 'Plugin version %s', 'admin-search' ), ADMIN_SEARCH_VERSION );
		}

		return $text;
	}, 15 );

}

add_action( 'admin_menu', 'admin_search_add_settings_page' );


/*
 *	Set default plugin settings and get user settings
 */
function admin_search_setting( $name, $fallback = NULL ) {

	$default_settings = array(
		'display_on_keypress'		=>	true,
		'autosearch'				=>	true,
		'query_highlight'			=>	false,
		'show_when_viewing_site'	=>	false,
		'result_previews'			=>	false,
		'show_suggestions'			=>	false,
		'post_types'				=>	array( 'post', 'page', 'attachment' ),
		'taxonomies'				=>	array( ),
		'include_admin_pages'		=>	true,
		'include_comments'			=>	false,
		'include_users'				=>	true,
		'external_websites'			=>	'',
		'result_count'				=>	5
	);

	$settings = get_option( 'admin_search_settings' );

	if ( $settings === false ) {
		$settings = array();
	}

	foreach ( $default_settings as $default_setting_name => $default_setting_value ) {
		if ( ! isset( $settings[ $default_setting_name ] ) ) {
			$settings[ $default_setting_name ] = $default_setting_value;
		}
	}

	if ( isset( $settings[ $name ] ) ) {
		return $settings[ $name ];
	}

	return $fallback;

}



/*
 *	Add plugin settings fields
 */
function admin_search_settings_init() {

	register_setting(
		'admin_search',
		'admin_search_settings',
		array(
			'sanitize_callback' => 'admin_search_sanitize_setting_values'
		)
	);


	// General settings
	add_settings_section(
		'admin_search_section_general',
		__( 'General', 'admin-search' ),
		'',
		'admin-search'
	);

	add_settings_field(
		'display_on_keypress',
		__( 'Display on Keypress', 'admin-search' ),
		'admin_search_setting_display_on_keypress',
		'admin-search',
		'admin_search_section_general'
	);

	add_settings_field(
		'autosearch',
		__( 'Search While Typing', 'admin-search' ),
		'admin_search_setting_autosearch',
		'admin-search',
		'admin_search_section_general'
	);

	add_settings_field(
		'highlight_query',
		__( 'Query Highlighting', 'admin-search' ),
		'admin_search_setting_highlight_query',
		'admin-search',
		'admin_search_section_general'
	);

	add_settings_field(
		'show_when_viewing_site',
		__( 'Show on Site', 'admin-search' ),
		'admin_search_setting_show_when_viewing_site',
		'admin-search',
		'admin_search_section_general'
	);

	add_settings_field(
		'result_previews',
		__( 'Result Previews', 'admin-search' ),
		'admin_search_setting_result_previews',
		'admin-search',
		'admin_search_section_general'
	);

	add_settings_field(
		'show_suggestions',
		__( 'Search Suggestions', 'admin-search' ),
		'admin_search_setting_show_suggestions',
		'admin-search',
		'admin_search_section_general'
	);

	add_settings_field(
		'result_count',
		__( 'Result Limit', 'admin-search' ),
		'admin_search_setting_result_count',
		'admin-search',
		'admin_search_section_general'
	);


	// Source settings
	add_settings_section(
		'admin_search_section_sources',
		__( 'Sources', 'admin-search' ),
		'admin_search_section_sources_text',
		'admin-search'
	);

	add_settings_field(
		'post_types',
		__( 'Post Types', 'admin-search' ),
		'admin_search_setting_post_types',
		'admin-search',
		'admin_search_section_sources'
	);

	add_settings_field(
		'taxonomies',
		__( 'Taxonomies', 'admin-search' ),
		'admin_search_setting_taxonomies',
		'admin-search',
		'admin_search_section_sources'
	);

	add_settings_field(
		'include_admin_pages',
		__( 'Admin Pages', 'admin-search' ),
		'admin_search_setting_include_admin_pages',
		'admin-search',
		'admin_search_section_sources'
	);

	add_settings_field(
		'include_comments',
		__( 'Comments', 'admin-search' ),
		'admin_search_setting_include_comments',
		'admin-search',
		'admin_search_section_sources'
	);

	add_settings_field(
		'include_users',
		__( 'Users', 'admin-search' ),
		'admin_search_setting_include_users',
		'admin-search',
		'admin_search_section_sources'
	);

	add_settings_field(
		'external_websites',
		__( 'External Websites', 'admin-search' ),
		'admin_search_setting_external_websites',
		'admin-search',
		'admin_search_section_sources'
	);

}

add_action( 'admin_init', 'admin_search_settings_init' );



/*
 *	Add plugin settings link to plugin item on Plugins page
 */
function admin_search_settings_page_link( $links ) {

	$links[] = '<a href="' . admin_url( 'options-general.php?page=admin-search' ) . '">' . __( 'Settings', 'admin-search' ) . '</a>';

	return $links;

}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'admin_search_settings_page_link' );




/*
 *	Render plugin settings page
 */
function admin_search_render_plugin_settings_page() {
	
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Admin Search Settings', 'admin-search' ); ?></h1>

	<form action="options.php" method="post">
	<?php
		do_settings_sections( 'admin-search' );

		settings_fields( 'admin_search' );

		submit_button();
	?>
	</form>
</div>
<?php

}



/*
 *	Sanitize and validate plugin settings
 */
function admin_search_sanitize_setting_values( $input ) {

	// Validate 'display_on_keypress' setting
	if ( isset( $input[ 'display_on_keypress' ] ) ) {
		$input[ 'display_on_keypress' ] = 1;
	} else {
		$input[ 'display_on_keypress' ] = 0;
	}

	// Validate 'autosearch' setting
	if ( isset( $input[ 'autosearch' ] ) ) {
		$input[ 'autosearch' ] = 1;
	} else {
		$input[ 'autosearch' ] = 0;
	}

	// Validate 'highlight_query' setting
	if ( isset( $input[ 'highlight_query' ] ) ) {
		$input[ 'highlight_query' ] = 1;
	} else {
		$input[ 'highlight_query' ] = 0;
	}

	// Validate 'show_when_viewing_site' setting
	if ( isset( $input[ 'show_when_viewing_site' ] ) ) {
		$input[ 'show_when_viewing_site' ] = 1;
	} else {
		$input[ 'show_when_viewing_site' ] = 0;
	}

	/*
	// Validate 'show_when_viewing_site' setting
	if ( isset( $input[ 'show_when_viewing_site' ] ) ) {
		$user_roles = new WP_Roles();
		$user_roles_names = $user_roles -> get_names();

		unset( $user_roles_names[ 'subscriber' ] );

		if ( ! in_array( $input[ 'show_when_viewing_site' ], $user_roles_names ) ) {
			$input[ 'show_when_viewing_site' ] = 'editor';
		}
	}
	*/

	// Validate 'result_previews' setting
	if ( isset( $input[ 'result_previews' ] ) ) {
		$input[ 'result_previews' ] = 1;
	} else {
		$input[ 'result_previews' ] = 0;
	}

	// Validate 'show_suggestions' setting
	if ( isset( $input[ 'show_suggestions' ] ) ) {
		$input[ 'show_suggestions' ] = 1;
	} else {
		$input[ 'show_suggestions' ] = 0;
	}

	// Validate 'post_types' setting
	if ( isset( $input[ 'post_types' ] ) ) {
		$post_types = array();

		foreach ( $input[ 'post_types' ] as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				$post_types[] = $post_type;
			}
		}

		$input[ 'post_types' ] = $post_types;
	}

	// Validate 'taxonomies' setting
	if ( isset( $input[ 'taxonomies' ] ) ) {
		$taxonomies = array();

		foreach ( $input[ 'taxonomies' ] as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$taxonomies[] = $taxonomy;
			}
		}

		$input[ 'taxonomies' ] = $taxonomies;
	}

	// Validate 'include_admin_pages' setting
	if ( isset( $input[ 'include_admin_pages' ] ) ) {
		$input[ 'include_admin_pages' ] = 1;
	} else {
		$input[ 'include_admin_pages' ] = 0;
	}

	// Validate 'include_comments' setting
	if ( isset( $input[ 'include_comments' ] ) ) {
		$input[ 'include_comments' ] = 1;
	} else {
		$input[ 'include_comments' ] = 0;
	}

	// Validate 'include_users' setting
	if ( isset( $input[ 'include_users' ] ) ) {
		$input[ 'include_users' ] = 1;
	} else {
		$input[ 'include_users' ] = 0;
	}

	// Validate 'external_websites' setting
	if ( isset( $input[ 'external_websites' ] ) ) {
		$external_websites = array();

		foreach ( explode( "\r\n", $input[ 'external_websites' ] ) as $external_website ) {
			if ( filter_var( $external_website, FILTER_VALIDATE_URL ) ) {
				$external_websites[] = trim( $external_website );
			}
		}

		$input[ 'external_websites' ] = implode( "\n", $external_websites );
	} else {
		$input[ 'external_websites' ] = '';
	}

	return $input;

}



/*
 *	Set up 'display_on_keypress' setting
 */
function admin_search_setting_display_on_keypress() {

	echo "<div id='admin_search_setting_display_on_keypress'><label><input type='checkbox' name='admin_search_settings[display_on_keypress]' value='1'";

	if ( admin_search_setting( 'display_on_keypress' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Display the Admin Search box on keypress', 'admin-search' ) . "</label></p><p class='description'>" . str_replace( array(
		'[code]',
		'[/code]'
	), array(
		'<code style="white-space: nowrap">',
		'</code>'
	), esc_html__( 'When enabled, the Admin Search box will display instantly on keypress except when a text field is in focus. The Admin Search box can be opened anytime by clicking the search icon in the admin bar.', 'admin-search' ) ) . "</p></div>";

}



/*
 *	Set up 'autosearch' setting
 */
function admin_search_setting_autosearch() {

	echo "<div id='admin_search_setting_autosearch'><label><input type='checkbox' name='admin_search_settings[autosearch]' value='1'";

	if ( admin_search_setting( 'autosearch' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Begin searching as you type', 'admin-search' ) . "</label></p><p class='description'>" . str_replace( array(
		'[code]',
		'[/code]'
	), array(
		'<code style="white-space: nowrap">',
		'</code>'
	), esc_html__( 'When enabled, search results will be displayed as you type. When disabled, press [code]Enter[/code] to search.', 'admin-search' ) ) . "</p></div>";

}



/*
 *	Set up 'result_count' setting
 */
function admin_search_setting_result_count() {

	$field = "<select name='admin_search_settings[result_count]'>";
	$options = array(
		5 => __( '5 (Recommended)', 'admin-search' ),
		10 => '10',
		20 => '20',
		50 => '50',
		100 => '100'
	);

	// Older versions of the plugin had different values, this will preserve legacy values until users change it
	if ( ! isset( $options[ admin_search_setting( 'result_count' ) ] ) ) {
		$options[ admin_search_setting( 'result_count' ) ] = admin_search_setting( 'result_count' );
	}

	foreach ( $options as $count => $label ) {
		$field .= "<option value='" . esc_attr( $count ) . "'";

		if ( $count == admin_search_setting( 'result_count' ) ) {
			$field .= " selected";
		}

		$field .= ">" . esc_html( $label ) . "</option>";
	}

	$field .= "</select>";

	/* translators: %s: the settings field, rendered as a dropdown inline with this label */
	echo "<div id='admin_search_setting_result_count'>" . sprintf( esc_html__( '%s results per source', 'admin-search' ), $field ) . "<p class='description'>" . esc_html__( 'If a search returns more results than the limit, an option to load more will be displayed in the search box. A high result limit may cause longer load times when searching.', 'admin-search' ) . "</p></div>";

}



/*
 *	Set up 'highlight_query' setting
 */
function admin_search_setting_highlight_query() {

	echo "<div id='admin_search_setting_highlight_query'><label><input type='checkbox' name='admin_search_settings[highlight_query]' value='1'";

	if ( admin_search_setting( 'highlight_query' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Highlight matching keywords in the search results', 'admin-search' ) . "</label></p></div>";

}



/*
 *	Set up 'show_when_viewing_site' setting
 */
function admin_search_setting_show_when_viewing_site() {

	echo "<div id='admin_search_setting_show_when_viewing_site'><label><input type='checkbox' name='admin_search_settings[show_when_viewing_site]' value='1'";

	if ( admin_search_setting( 'show_when_viewing_site' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Allow access to the Admin Search box on the website', 'admin-search' ) . "</label></p><p class='description'>" . esc_html__( 'When enabled, the Admin Search box will be accessible to admins when viewing the website.', 'admin-search' ) . "</p></div>";

}



/*
 *	Set up 'result_previews' setting
 */
function admin_search_setting_result_previews() {

	echo "<div id='admin_search_setting_result_previews'><label><input type='checkbox' name='admin_search_settings[result_previews]' value='1'";

	if ( admin_search_setting( 'result_previews' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Display previews in search results', 'admin-search' ) . "<span style='margin-left:4px;padding:4px 6px;background:rgb(34 112 177 / 10%);color:rgb(34 112 177);font-weight:500;border-radius:2px'>" . esc_html__( 'Beta', 'admin-search' ) . "</span></label></p><p class='description'>" . esc_html__( 'When enabled, permalinks of supported search results can be previewed directly in the Admin Search box.', 'admin-search' ) . "</p></div>";

}



/*
 *	Set up 'show_suggestions' setting
 */
function admin_search_setting_show_suggestions() {

	echo "<div id='admin_search_setting_show_suggestions'><label><input type='checkbox' name='admin_search_settings[show_suggestions]' value='1'";

	if ( admin_search_setting( 'show_suggestions' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Show suggestions as you type', 'admin-search' ) . "<span style='margin-left:4px;padding:4px 6px;background:rgb(34 112 177 / 10%);color:rgb(34 112 177);font-weight:500;border-radius:2px'>" . esc_html__( 'Beta', 'admin-search' ) . "</span></label></p><p class='description'>";

	global $wpdb;

	$table_name = $wpdb -> prefix . 'admin_search__searches';
	$top_searches = $wpdb -> get_results( $wpdb -> prepare( "SELECT * FROM {$table_name} ORDER BY LENGTH(query) DESC, occurrences DESC, results DESC LIMIT 100" ) );

	if ( empty( $top_searches ) ) {
		esc_html_e( 'When enabled, search suggestions will be displayed as you type. These suggestions are based on past searches – the more you search, the more accurate the suggestions become.', 'admin-search' );
	} else {
		echo str_replace(
			array(
				'[span clearSuggestionContainer]',
				'[a clearSuggestions]',
				'[/a]',
				'[/span]'
			),
			array(
				'<span id="admin-search-clear-searches-button">',
				'<a href="#" onclick="AS_clear_searches(\'' . wp_create_nonce( 'clear-searches' ) . '\')" style="color: #d63638">',
				'</a>',
				'</span>'
			),
			esc_html__( 'When enabled, search suggestions will be displayed as you type. These suggestions are based on past searches – the more you search, the more accurate the suggestions become.[span clearSuggestionContainer] [a clearSuggestions]Clear search suggestions[/a].[/span]', 'admin-search' )
		);
	}
	echo "</p></div>";

}



/*
 *	Add 'sources' settings section summary copy
 */
function admin_search_section_sources_text() {

	echo '<p>' . esc_html__( 'Choose what appears in search results.', 'admin-search' ) . '</p>';

}



/*
 *	Set up 'post_types' setting
 */
function admin_search_setting_post_types() {

	echo "<div id='admin_search_plugin_setting_post_types'>";

	foreach ( get_post_types( array(), 'object' ) as $post_type ) {
		if ( $post_type -> {'public'} || $post_type -> name === 'wp_block' ) {
			echo "<p><label><input type='checkbox' name='admin_search_settings[post_types][]' value='" . esc_attr( $post_type -> name ) . "'";

			if ( in_array( $post_type -> name, admin_search_setting( 'post_types', array() ) ) ) {
				echo " checked";
			}

			echo "> " . esc_html( $post_type -> label ) . "</label></p>";
		}
	}

	echo "</div>";

}



/*
 *	Set up 'taxonomies' setting
 */
function admin_search_setting_taxonomies() {

	echo "<div id='admin_search_plugin_setting_taxonomies'>";

	foreach ( get_taxonomies( array(), 'object' ) as $taxonomy ) {
		if ( $taxonomy -> {'public'} ) {
			echo "<p><label><input type='checkbox' name='admin_search_settings[taxonomies][]' value='" . esc_attr( $taxonomy -> name ) . "'";

			if ( in_array( $taxonomy -> name, admin_search_setting( 'taxonomies', array() ) ) ) {
				echo " checked";
			}

			echo "> " . esc_html( $taxonomy -> label ) . "</label></p>";
		}
	}

	echo "</div>";

}



/*
 *	Set up 'include_admin_pages' setting
 */
function admin_search_setting_include_admin_pages() {

	echo "<div id='admin_search_setting_include_admin_pages'><label><input type='checkbox' name='admin_search_settings[include_admin_pages]' value='1'";

	if ( admin_search_setting( 'include_admin_pages' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Include admin pages in the search results', 'admin-search' ) . "</label><p class='description'>" . esc_html__( 'Users will only see admin pages they have access to.', 'admin-search' ) . "</p></div>";

}



/*
 *	Set up 'include_comments' setting
 */
function admin_search_setting_include_comments() {

	echo "<div id='admin_search_setting_include_comments'><label><input type='checkbox' name='admin_search_settings[include_comments]' value='1'";

	if ( admin_search_setting( 'include_comments' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Include comments in the search results', 'admin-search' ) . "</label></div>";

}



/*
 *	Set up 'include_users' setting
 */
function admin_search_setting_include_users() {

	echo "<div id='admin_search_setting_include_users'><label><input type='checkbox' name='admin_search_settings[include_users]' value='1'";

	if ( admin_search_setting( 'include_users' ) ) {
		echo " checked";
	}

	echo "> " . esc_html__( 'Include user profiles in the search results', 'admin-search' ) . "</label></div>";

}



/*
 *	Set up 'external_websites' setting
 */
function admin_search_setting_external_websites() {

	echo "<div id='admin_search_setting_external_websites'><textarea name='admin_search_settings[external_websites]' class='large-text code' rows='3' spellcheck='false'>";

	if ( admin_search_setting( 'external_websites' ) ) {
		echo esc_html( admin_search_setting( 'external_websites' ) );
	}

	echo "</textarea><p class='description'>" . str_replace( array(
		'[code]',
		'[/code]'
	), array(
		'<code style="white-space: nowrap">',
		'</code>'
	), esc_html__( 'Display links to external websites in search results. Use [code]%q%[/code] in place of the query value in the URL. Separate multiple website URLs with line breaks.', 'admin-search' ) ) . "</p><p class='description'>" . str_replace( array(
		'[code]',
		'[/code]'
	), array(
		'<code style="white-space: nowrap">',
		'</code>'
	), esc_html__( 'Example: Add [code]https://wordpress.org/search/%q%[/code] to display a link to the WordPress.org search pre-populated with your search query.', 'admin-search' ) ) . "</p></div>";

}
