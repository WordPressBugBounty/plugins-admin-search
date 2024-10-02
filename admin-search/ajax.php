<?php
/*
 *	Format date strings for WP_Query argument
 */
function admin_search_convert_date_str(  $str = ''  ) {

	$r = array();
	$days = array(
		__( 'Monday' ),
		__( 'Tuesday' ),
		__( 'Wednesday' ),
		__( 'Thursday' ),
		__( 'Friday' ),
		__( 'Saturday' ),
		__( 'Sunday' )
	);
	$months = array(
		__( 'January' ),
		__( 'February' ),
		__( 'March' ),
		__( 'April' ),
		__( 'May' ),
		__( 'June' ),
		__( 'July' ),
		__( 'August' ),
		__( 'September' ),
		__( 'October' ),
		__( 'November' ),
		__( 'December' )
	);

	switch ( $str ) {
		// 4 digit year
		case is_numeric( $str ) && strlen( $str ) == 4 :
			$date = strtotime( $str . '-01-01' );
			$r[ 'year' ] = gmdate( 'Y', $date );
			break;

		// Year ago string
		case in_array( $str, array(
			__( 'last year', 'admin-search' ),
			__( 'a year ago', 'admin-search' ),
			__( 'one year ago', 'admin-search' )
		) ) :
			$date = strtotime( 'last year' );
			$r[ 'year' ] = gmdate( 'Y', $date );
			break;

		// Month ago string
		case in_array( $str, array(
			__( 'last month', 'admin-search' ),
			__( 'a month ago', 'admin-search' ),
			__( 'one month ago', 'admin-search' )
		) ) :
			$date = strtotime( 'last month' );
			$r[ 'year' ] = gmdate( 'Y', $date );
			$r[ 'month' ] = gmdate( 'm', $date );
			break;

		// Day as string
		case strtolower( gmdate( 'l' ) ) == $str :
			$date = strtotime( 'NOW' );
			$r[ 'year' ] = gmdate( 'Y', $date );
			$r[ 'month' ] = gmdate( 'm', $date );
			$r[ 'day' ] = gmdate( 'd', $date );
			break;

		// Last day as string
		case in_array( ucfirst( $str ), $days ) :
			$date = strtotime( 'last ' . $str );
			$r[ 'year' ] = gmdate( 'Y', $date );
			$r[ 'month' ] = gmdate( 'm', $date );
			$r[ 'day' ] = gmdate( 'd', $date );
			break;

		// Month as string
		case in_array( ucfirst( $str ), $months ) :
			if ( gmdate( 'm', strtotime( $str . ' ' . gmdate( 'Y' ) ) ) > gmdate( 'm' ) ) {
				$date = strtotime( $str . ' ' . gmdate( 'Y', strtotime( '-1 year' ) ) );
			} else {
				$date = strtotime( $str . ' ' . gmdate( 'Y' ) );
			}

			$r[ 'year' ] = gmdate( 'Y', $date );
			$r[ 'month' ] = gmdate( 'm', $date );
			break;

		// Any other native format
		default :
			$date = strtotime( $str );
			$r[ 'year' ] = gmdate( 'Y', $date );
			$r[ 'month' ] = gmdate( 'm', $date );
			$r[ 'day' ] = gmdate( 'd', $date );
	}

	return $r;

}


/*
 *	Add a nice title to external website names
 */
function admin_search_get_website_title( $host ) {

	// Some common websites
	$website_titles = array(
		'bing.com' => 'Bing',
		'gettyimages.com' => 'Getty Images',
		'google.ca' => 'Google',
		'google.ch' => 'Google',
		'google.co.nz' => 'Google',
		'google.co.uk' => 'Google',
		'google.com' => 'Google',
		'google.com.au' => 'Google',
		'google.in' => 'Google',
		'istockphoto.com' => 'iStock',
		'shutterstock.com' => 'Shutterstock',
		'stackoverflow.com' => 'Stack Overflow',
		'unsplash.com' => 'Unsplash',
		'wikipedia.org' => 'Wikipedia',
		'en.wikipedia.org' => 'Wikipedia',
		'wordpress.org' => 'WordPress',
		'wordpress.stackexchange.com' => 'WordPress Development Stack Exchange',
		'wpbeginner.com' => 'WPBeginner',
		'yahoo.com' => 'Yahoo'
	);

	// Apply any filters to the array
	$website_titles = apply_filters( 'admin_search_website_titles', $website_titles );

	// Check if array is valid and host exists, then return matching label
	if ( is_array( $website_titles ) && isset( $website_titles[ $host ] ) ) {
		return $website_titles[ $host ];
	}

	// Otherwise, return the host name as is
	return $host;

}


/*
 *	Prepare JSON for AJAX call
 */
function admin_search_ajax_return(  $message = '', $error = true, $results = array(), $total_results = 0  ) {

	wp_send_json( array(
		'error'			=>	$error,
		'message'		=>	$message,
		'results'		=>	$results,
		'total_results'	=>	$total_results
	) );

	wp_die();

	return ! $error;

}


/*
 *	Prepare query for LIKE statement
 */
function admin_search_prepare_query(  $table, $field, $s  ) {

	global $wpdb;

	return $wpdb -> prepare( "{$table}.{$field} LIKE %s", '%' . $wpdb -> esc_like( $s ) . '%' );

}


/*
 *	Add 'as_s' item to WP_Query and handle it
 */
function admin_search_extend_query(  $where, $wp_query  ) {

	global $wpdb;

	// Only modify this filter if 'as_s' is used in the WP_Query call
	if ( $s = $wp_query -> get( 'as_s' ) ) {
		$default_fields = array( 'post_title', 'post_name', 'post_excerpt', 'post_content' );

		// Apply filters to search fields
		$fields = apply_filters( 'admin_search_fields', $default_fields, $wp_query -> get( 'post_type' ) );

		// Check that applied filters are valid, otherwise, use default fields
		if ( ! ( is_array( $fields ) && ! empty( $fields ) ) ) {
			$fields = $default_fields;
		}

		// Build WHERE query
		$where .= ' AND (';

		foreach ( $fields as $i => $field ) {
			if ( $i > 0 ) {
				$where .= ' OR ';
			}

			$where .= admin_search_prepare_query( $wpdb -> posts, $field, $s );
		}

		// Apply filters to meta queries
		$meta_fields = apply_filters( 'admin_search_meta_queries', array( '_wp_attachment_image_alt' ), $wp_query -> get( 'post_type' ) );

		// If meta queries have been added, build WHERE query
		if ( is_array( $meta_fields ) && ! empty( $meta_fields ) ) {
			if ( ! empty( $fields ) ) {
				$where .= ' OR ';
			}

			foreach ( $meta_fields as $i => $meta_field ) {
				if ( $i > 0 ) {
					$where .= ' OR ';
				}

				$where .= '(' . admin_search_prepare_query( $wpdb -> postmeta, 'meta_value', $s ) . " AND {$wpdb -> postmeta}.meta_key = '{$meta_field}')";
			}
		}

		$where .= ')';
	}

	return $where;

}


/*
 *	Enable 'as_s' for meta queries
 */
add_filter( 'posts_where', 'admin_search_extend_query', 10, 2 );
add_filter( 'posts_join', function(  string $sql, WP_Query $query  ) {

	global $wpdb;

	if ( $query -> get( 'as_s' ) ) {
		$sql .= " LEFT JOIN {$wpdb -> postmeta} ON {$wpdb -> posts}.ID = {$wpdb -> postmeta}.post_id ";
	}

	return $sql;

}, 10, 2 );

add_filter( 'posts_distinct', function(  string $sql, WP_Query $query  ) {

	if ( $query -> get( 'as_s' ) ) {
		return 'DISTINCT';
	}

	return $sql;

}, 10, 2 );


/*
 *	Validate search query and return search results with AJAX
 */
function admin_search_ajax() {

	// Only logged in users can search
	if ( ! is_user_logged_in() ) {
		return admin_search_ajax_return( __( 'Not authorized', 'admin-search' ) );
	}

	// Only users who can edit other users' posts can search
	if ( ! current_user_can( 'edit_others_pages' ) ) {
		return admin_search_ajax_return( __( 'Not authorized', 'admin-search' ) );
	}

	// JSON headers and default values
	$results = array();


	// Perform basic checks on q
	if ( ! ( isset( $_GET[ 'q' ] ) && is_string( trim( wp_strip_all_tags( $_GET[ 'q' ] ) ) ) ) ) {
		return admin_search_ajax_return( __( 'No search query supplied', 'admin-search' ) );
	}

	// Clean q up
	$q = trim( strtolower( wp_strip_all_tags( $_GET[ 'q' ] ) ) );

	// Apply any filters to q
	$q = apply_filters( 'admin_search_query', $q );

	// Check if source is supplied, otherwise build list from preferences
	if ( isset( $_GET[ 'source' ] ) && is_string( trim( $_GET[ 'source' ] ) ) ) {
		$sources = array( $_GET[ 'source' ] );
	} else {
		$sources = array_merge( admin_search_setting( 'post_types', array() ), admin_search_setting( 'taxonomies', array() ) );

		if ( admin_search_setting( 'include_comments' ) ) {
			$sources[] = 'comment';
		}

		if ( admin_search_setting( 'include_users' ) ) {
			$sources[] = 'user';
		}

		if ( admin_search_setting( 'include_admin_pages' ) ) {
			$sources[] = 'admin';
		}

		if ( admin_search_setting( 'external_websites' ) ) {
			$sources[] = 'external_sites';
		}
	}

	// Apply any filters to sources array
	$sources = apply_filters( 'admin_search_sources', $sources, $q );

	// Make sure the filters didn't break the array
	if ( ! is_array( $sources ) ) {
		return admin_search_ajax_return( __( 'Invalid sources list', 'admin-search' ) );
	}


	// Check if q is an ID
	$id = NULL;

	if ( preg_match( '/^#([0-9]+)$/', $q, $match ) ) {
		$id = $match[ 1 ];
	}


	// Check if q contains a status, if it does, limit it to just posts, pages, attachments and custom post types
	$status = 'any';
	
	/* translators: %s: post status (eg. draft, published, pending) */
	$status_pattern = '/\s' . sprintf( __( 'status:%s', 'admin-search' ), '([a-z]+)' ) . '$/';

	if ( preg_match( $status_pattern, $q, $match ) ) {
		$status = $match[ 1 ];
		$q = preg_replace( $status_pattern, '', $q );
		$sources = admin_search_setting( 'post_types', array() );
	}


	// Check if paged is supplied and valid, if not, set as 1
	$paged = isset( $_GET[ 'paged' ] ) && $_GET[ 'paged' ] > 0 ? $_GET[ 'paged' ] : 1;


	// Check if q is long enough, afterall, anything shorter than 2 characters is not a very good search
	if ( strlen( $q ) < 2 ) {
		return admin_search_ajax_return( __( 'Query too short', 'admin-search' ) );
	}


	// Apply any filters to $results
	$results = apply_filters( 'admin_search_pre_results', $results, $q );


	// Cycle through all the searchable post types
	foreach ( admin_search_setting( 'post_types', array() ) as $post_type ) {
		// Check if the post type is in the sources array, if not, skip it
		if ( ! in_array( $post_type, $sources ) ) {
			continue;
		}

		// Setup author and date searches
		$author = NULL;
		$date = NULL;
		$date_range = 'on';

		// Check if q matches any of these author search terms
		foreach ( array(
			__( 'posts by %author%', 'admin-search' ),
			__( 'posted by %author%', 'admin-search' ),
			__( 'authored by %author%', 'admin-search' ),
			__( 'author is %author%', 'admin-search' ),
			__( 'author %author%', 'admin-search' ),
			__( 'made by %author%', 'admin-search' ),
			__( 'created by %author%', 'admin-search' )
		) as $author_q ) {
			if ( preg_match( '/^' . str_replace( '%author%', '(.+)', $author_q ) . '$/', $q, $match ) ) {
				$author = $match[ 1 ]; break;
			}
		}

		// Check if q matches any of these date search term
		if ( $author == NULL ) {
			// Start with dates before a given date

			foreach ( array(
				/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
				__( 'posted before %date%', 'admin-search' ),
				/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
				__( 'published before %date%', 'admin-search' )
			) as $date_q ) {
				if ( preg_match( '/^' . str_replace( '%date%', '(.+)', $date_q ) . '$/', $q, $match ) ) {
					$date = admin_search_convert_date_str( $match[ 1 ] );
					$date_range = 'before'; break;
				}
			}

			// Followed by dates after a given date

			if ( $date == NULL ) {
				foreach ( array(
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'posted after %date%', 'admin-search' ),
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'published after %date%', 'admin-search' )
				) as $date_q ) {
					if ( preg_match( '/^' . str_replace( '%date%', '(.+)', $date_q ) . '$/', $q, $match ) ) {
						$date = admin_search_convert_date_str( $match[ 1 ] );
						$date_range = 'after'; break;
					}
				}
			}

			// Otherwise, does it match any of these date search terms?

			if ( $date == NULL ) {
				foreach ( array(
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'posted on %date%', 'admin-search' ),
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'posted in %date%', 'admin-search' ),
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'posted %date%', 'admin-search' ),
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'published on %date%', 'admin-search' ),
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'published in %date%', 'admin-search' ),
					/* translators: %date%: any date format (see this list: https://wordpress.org/documentation/article/customize-date-and-time-format/) */
					__( 'published %date%', 'admin-search' )
				) as $date_q ) {
					if ( preg_match( '/^' . str_replace( '%date%', '(.+)', $date_q ) . '$/', $q, $match ) ) {
						$date = admin_search_convert_date_str( $match[ 1 ] ); break;
					}
				}
			}
		}


		// Setup `WP_Post` arguments
		$posts_args = array(
			'post_type'				=>	$post_type,
			'post_status'			=>	$status,
			'posts_per_page'		=>	admin_search_setting( 'result_count' ),
			'paged'					=>	$paged,
			'ignore_sticky_posts'	=>	true
		);

		// If there is an author or a date in the search, add it to the arguments, otherwise, check for an ID and finally, just pass the q into the s argument
		if ( $author != NULL ) {
			if ( is_numeric( $author ) ) {
				$posts_args[ 'author' ] = $author;
			} else {
				$posts_args[ 'author_name' ] = $author;
			}
		} else if ( $date != NULL ) {
			switch ( $date_range ) {
				case 'on' :
					$posts_args[ 'date_query' ] = $date;
					break;
				case 'before' :
					$posts_args[ 'date_query' ]['before'] = $date;
					break;
				case 'after' :
					$posts_args[ 'date_query' ]['after'] = $date;
					break;
			}
		} else if ( $id ) {
			$posts_args[ 'p' ] = $id;
		} else {
			$posts_args[ 'as_s' ] = $q;
		}

		// Apply any filters to the `WP_Query` arguments
		$posts_args = apply_filters( 'admin_search_posts_query', $posts_args, $q );
		$posts_args = apply_filters( 'admin_search_' . $post_type . '_query', $posts_args, $q );

		// Perform `WP_Query`
		$posts = new WP_Query( $posts_args );

		// Check if there are any posts that match all that criteria
		if ( $posts -> have_posts() ) {
			// There are!
			while ( $posts -> have_posts() ) {
				$posts -> the_post();

				// If the source information hasn't already been defined, define it. This has to be done within the loop, so if it is defined, just ignore it. Gross
				if ( ! isset( $results[ $post_type ] ) ) {
					// Get the post type fields to add to the source information
					$post_type_object = get_post_type_object( get_post_type( get_the_id() ) );

					$results[ $post_type ][ 'post_type' ] = array(
						'name' => $post_type,
						'label' => $post_type_object -> label,
						'search_url' => add_query_arg( array(
							'post_type' => $post_type,
							's' => $q
						), get_admin_url( '', 'edit.php' ) )
					);

					// The attachment post type has a different search URL
					if ( $post_type == 'attachment' ) {
						$results[ $post_type ][ 'post_type' ][ 'search_url' ] = add_query_arg( array(
							'search' => $q
						), get_admin_url( '', 'upload.php' ) );
					}
				}

				$title = get_the_title();
				$content =  apply_filters( 'the_content', get_the_content() );

				// If the highlight query setting is enabled, replace the matching content with the highlight HTML
				if ( admin_search_setting( 'highlight_query' ) ) {
					$title = preg_replace( '/(' . addcslashes( $q, '/' ) . ')/i', '<span class="admin-search-result-title-highlight">$1</span>', $title );
					$content = preg_replace( '/(' . addcslashes( $q, '/' ) . ')/i', '<span class="admin-search-result-title-highlight">$1</span>', $content );
				}

				// Build the result array
				$result = array(
					'id'				=>	get_the_id(),
					'edit_post_link'	=>	get_edit_post_link(),
					'preview_link'		=>	null,
					'title'				=>	$title,
					'date'				=>	get_the_date(),
					'status'			=>	get_post_status() == 'publish' ? NULL : ucfirst( get_post_status() )
				);

				// Check if `results_previews` are enabled
				if ( admin_search_setting( 'result_previews' ) ) {
					$result[ 'preview_link' ] = add_query_arg( 'admin_search_preview', 'true', get_preview_post_link() );
				}

				// Special items for attachments
				if ( $post_type == 'attachment' ) {
					$result[ 'attachment' ] = wp_get_attachment_image( get_the_id(), 'medium' );
					$result[ 'mime_type' ] = get_post_mime_type();
					$result[ 'mime_icon' ] = wp_mime_type_icon( get_the_id() );
				}

				// Add the result array to $return
				$results[ $post_type ][ 'posts' ][] = $result;
			}

			// Add addition information about this source to $return
			$results[ $post_type ][ 'total_results' ] = $posts -> found_posts;
			$results[ $post_type ][ 'total_pages' ] = $posts -> max_num_pages;
			$results[ $post_type ][ 'current_page' ] = $paged;
			$results[ $post_type ][ 'next_page' ] = NULL;

			// If there is more than one page of results, add the page number
			if ( $posts -> max_num_pages > $paged ) {
				$results[ $post_type ][ 'next_page' ] = $paged + 1;
			}
		}

		wp_reset_query();
	}


	// Cycle through all the searchable taxonomies
	foreach ( admin_search_setting( 'taxonomies', array() ) as $taxonomy ) {
		// Check if this taxonomy is in the sources array, if not, skip it
		if ( ! in_array( $taxonomy, $sources ) ) {
			continue;
		}

		// Setup arguments for `WP_Term_Query`
		$terms_args = array(
			'taxonomy'		=>	$taxonomy,
			'hide_empty'	=>	false,
			'number'		=>	admin_search_setting( 'result_count' ),
			'offset'		=>	(int)( $paged - 1 ) * (int)admin_search_setting( 'result_count' )
		);

		// If q is an ID, only look for the term with that ID, otherwise, pass q to the `name__like` argument
		if ( $id ) {
			$terms_args[ 'include' ] = array( $id );
		} else {
			$terms_args[ 'name__like' ] = $q;
		}

		// Apply any filters to the `WP_Term_Query` arguments
		$terms_args = apply_filters( 'admin_search_terms_query', $terms_args, $q );
		$terms_args = apply_filters( 'admin_search_' . $taxonomy . '_query', $terms_args, $q );

		// Perform `WP_Term_Query`
		$terms = new WP_Term_Query( $terms_args );

		// Check if there are any terms matching that criteria
		if ( $terms -> get_terms() ) {
			// There are!
			foreach ( $terms -> get_terms() as $term ) {
				if ( ! isset( $results[ $taxonomy ] ) ) {
					// Get the taxonomy fields to add to the source information
					$taxonomy_object = get_taxonomy( $term -> taxonomy );

					// Set the post type for the queried taxonomy
					$taxonomy_post_type = '';

					if ( is_array( $taxonomy_object -> object_type ) ) {
						$taxonomy_post_type = $taxonomy_object -> object_type[ 0 ];
					}

					// If the source information hasn't already been defined, define it. This has to be done within the loop, so if it is defined, just ignore it. Gross
					$results[ $taxonomy ][ 'post_type' ] = array(
						'name'	=> $taxonomy,
						'label'	=> $taxonomy_object -> label,
						'search_url' => add_query_arg( array(
							'taxonomy' => $taxonomy,
							'post_type' => $taxonomy_post_type,
							's' => $q
						), get_admin_url( '', 'edit-tags.php' ) )
					);
				}

				$title = $term -> name;

				// If the highlight query setting is enabled, replace the matching content with the highlight HTML
				if ( admin_search_setting( 'highlight_query' ) ) {
					$title = preg_replace( '/(' . addcslashes( $q, '/' ) . ')/i', '<span class="admin-search-result-title-highlight">$1</span>', $title );
				}

				// Build the result array and add it to $return
				$results[ $taxonomy ][ 'posts' ][] = array(
					'id'				=>	$term -> term_id,
					'edit_post_link'	=>	get_edit_term_link( $term -> term_id, $taxonomy ),
					'title'				=>	$title,
				);
			}

			// Add addition information about this source to $return. A little more work is required because `WP_Term_Query` doesn't return total found items or number of pages
			$found_terms = wp_count_terms( $terms_args );
			$total_pages = ceil( $found_terms / admin_search_setting( 'result_count' ) );
			$results[ $taxonomy ][ 'total_results' ] = $found_terms;
			$results[ $taxonomy ][ 'total_pages' ] = $total_pages;
			$results[ $taxonomy ][ 'current_page' ] = $paged;
			$results[ $taxonomy ][ 'next_page' ] = NULL;

			// If there is more than one page of results, add the page number
			if ( $total_pages > $paged ) {
				$results[ $taxonomy ][ 'next_page' ] = $paged + 1;
			}
		}
	}

	// Check if comment is in the sources array, if not, skip it
	if ( in_array( 'comment', $sources ) ) {
		// Setup arguments for `WP_Comment_Query`
		$comments_args = array(
			'search'	=>	$q,
			'number'	=>	admin_search_setting( 'result_count' ),
			'offset'	=>	( $paged - 1 ) * admin_search_setting( 'result_count' )
		);

		// Apply any filters to the `WP_Comment_Query` arguments
		$comments_args = apply_filters( 'admin_search_comments_query', $comments_args, $q );

		// Perform `WP_Comment_Query`
		$comments = new WP_Comment_Query( $comments_args );

		// Check if there any comments matching that criteria
		if ( $comments -> comments ) {
			// There are!
			// Define source information
			$results[ 'comment' ][ 'post_type' ] = array(
				'name'	=>	'comment',
				'label'	=>	__( 'Comments' ),
				'search_url' => add_query_arg( array(
					's' => $q
				), get_admin_url( '', 'edit-comments.php' ) )
			);

			foreach ( $comments -> comments as $comment ) {
				$title = get_the_title( $comment -> comment_post_ID );

				// If the highlight query setting is enabled, replace the matching content with the highlight HTML
				if ( admin_search_setting( 'highlight_query' ) ) {
					$title = preg_replace( '/(' . addcslashes( $q, '/' ) . ')/i', '<span class="admin-search-result-title-highlight">$1</span>', $title );
				}

				// Build the result array and add it to $return
				$results[ 'comment' ][ 'posts' ][] = array(
					'id'				=>	$comment -> comment_ID,
					'edit_post_link'	=>	get_edit_comment_link( $comment -> comment_ID ),

					/* translators: %author%: comment author, %title%: comment's post title */
					'title'				=>	str_replace( array(
												'%author%',
												'%title%'
											), array(
												$comment -> comment_author,
												$title
											), __( 'Comment by %author% on %title%' , 'admin-search' ) ),
					'date'				=>	gmdate( get_option( 'date_format' ), strtotime( $comment -> comment_date ) ),
					'status'			=>	$comment -> comment_approved ? 'Published' : 'Pending'
				);
			}

			// Add addition information about this source to $return
			$results[ 'comment' ][ 'total_results' ] = $comments -> found_comments;
			$results[ 'comment' ][ 'total_pages' ] = $comments -> max_num_pages;
			$results[ 'comment' ][ 'current_page' ] = $paged;
			$results[ 'comment' ][ 'next_page' ] = NULL;

			// If there is more than one page of results, add the page number
			if ( $comments -> max_num_pages > $paged ) {
				$results[ 'comment' ][ 'next_page' ] = $paged + 1;
			}
		}
	}


	// Check if user is in the sources array, if not, skip it, and if the logged in user can edit users. Don't want users having access to other user profiles!
	if ( in_array( 'user', $sources ) && current_user_can( 'edit_user' ) ) {		
		// Fetch a list of all user roles
		$user_roles = new WP_Roles();
		$role_names = $user_roles -> get_names();
		$role_q = $q;

		// Add extra terms for administrator
		if ( in_array( $role_q, array( 'admin', 'admins' ) ) ) {
			$role_q = 'administrator';
		}

		// Setup arguments for `WP_User_Query`
		$user_args = array(
			'number'	=>	admin_search_setting( 'result_count' ),
			'paged'		=>	$paged
		);

		// Depluralize role names (this isn't perfect), then check if q is a role, if it is, pass it to the role argument, otherwise, pass q to the search argument and add search columns
		if ( isset( $role_names[ preg_replace( '/s$/', '', $role_q ) ] ) ) {
			$user_args[ 'role' ] = preg_replace( '/s$/', '', $role_q );
		} else {
			if ( $id ) {
				$user_args = array(
					'search'         	=>	'*' . $id . '*',
					'search_columns'	=>	array(
						'ID'
					)
				);
			} else {
				$user_args = array(
					'search'         	=>	'*' . $q . '*',
					'search_columns'	=>	array(
						'ID',
						'user_login',
						'user_nicename',
						'user_email',
						'display_name',
					)
				);
			}
		}

		// Apply any filters to `WP_User_Query` arguments
		$user_args = apply_filters( 'admin_search_users_query', $user_args, $q );

		// Perform `WP_User_Query`
		$users = new WP_User_Query( $user_args );

		// Check if there are any users matching that criteria
		if ( $users -> get_results() ) {
			// There are!

			// If q is a role, then change the search URL to the role URL
			if ( isset( $user_args[ 'role' ] ) ) {
				$results[ 'user' ][ 'post_type' ][ 'search_url' ] = add_query_arg( array(
					'role' => $role_q
				), get_admin_url( '', 'users.php' ) );
			}

			foreach ( $users -> get_results() as $user ) {
				// Check if user is hidden to Admin Search
				if ( get_the_author_meta( 'hide_in_admin_search', $user -> ID ) ) {
					continue;
				}

				$title = $user -> data -> user_login;

				// If the highlight query setting is enabled, replace the matching content with the highlight HTML
				if ( admin_search_setting( 'highlight_query' ) ) {
					$title = preg_replace( '/(' . addcslashes( $q, '/' ) . ')/i', '<span class="admin-search-result-title-highlight">$1</span>', $title );
				}

				// Build the result array and add it to $return
				$results[ 'user' ][ 'posts' ][] = array(
					'id'				=>	$user -> ID,
					'edit_post_link'	=>	get_edit_user_link( $user -> ID ),
					'title'				=>	$title,
					'date'				=>	NULL,
					'status'			=>	$role_names[ $user -> roles[ 0 ] ]
				);
			}

			// Define source information
			if ( isset( $results[ 'user' ] ) ) {
				$results[ 'user' ][ 'post_type' ] = array(
					'name'			=>	'user',
					'label'			=>	__( 'Users' ),
					'search_url'	=> add_query_arg( array(
						's' => $q
					), get_admin_url( '', 'users.php' ) )
				);

				// Add addition information about this source to $return. A little more work is required because `WP_User_Query` doesn't return total pages
				$total_pages = ceil( $users -> get_total() / admin_search_setting( 'result_count' ) );
				$results[ 'user' ][ 'total_results' ] = $users -> get_total();
				$results[ 'user' ][ 'total_pages' ] = $total_pages;
				$results[ 'user' ][ 'current_page' ] = $paged;
				$results[ 'user' ][ 'next_page' ] = NULL;

				// If there is more than one page of results, add the page number
				if ( $total_pages > $paged ) {
					$results[ 'user' ][ 'next_page' ] = $paged + 1;
				}
			}
		}
	}


	// Check if admin is in the sources array, if not, skip it
	if ( in_array( 'admin', $sources ) ) {
		// Add admin page search terms
		require_once plugin_dir_path( __FILE__ ) . 'admin-results.php';

		// Cycle through the admin page search terms
		foreach ( $admin_results as $admin_result ) {
			// Check if the logged in user has the cability to access this admin page
			if ( current_user_can( $admin_result[ 'capability' ] ) ) {
				// Check if q contains the admin page term
				$contains_value = false;

				foreach ( $admin_result[ 'q' ] as $sub_q ) {
					if ( strpos( strtolower( $sub_q ), $q ) !== false ) {
						$contains_value = true; break;
					}
				}

				// It does!
				if ( $contains_value ) {
					$title = $admin_result[ 'title' ];

					// If the highlight query setting is enabled, replace the matching content with the highlight HTML
					if ( admin_search_setting( 'highlight_query' ) ) {
						$title = preg_replace( '/(' . addcslashes( $q, '/' ) . ')/i', '<span class="admin-search-result-title-highlight">$1</span>', $title );
					}

					// Build the result array and add it to $return
					$results[ 'admin' ][ 'posts' ][] = array(
						'id'				=>	'',
						'edit_post_link'	=>	$admin_result[ 'url' ],
						'title'				=>	$title,
						'status'			=>	$admin_result[ 'status' ]
					);
				}
			}
		}

		// If there are results, define source information
		if ( isset( $results[ 'admin' ] ) ) {
			$results[ 'admin' ][ 'post_type' ] = array(
				'name'	=>	'admin',
				'label'	=>	__( 'Admin Pages', 'admin-search' )
			);
		}
	}


	// Check if admin is in the sources array, if not, skip it
	if ( in_array( 'external_sites', $sources ) ) {
		// Define source information
		$results[ 'external_sites' ][ 'post_type' ] = array(
			'name'	=>	'external_sites',
			'label'	=>	__( 'Other Websites', 'admin-search' )
		);

		// Break the setting field up into an array and cycle through it
		foreach ( explode( "\n", admin_search_setting( 'external_websites' ) ) as $external_website ) {
			// Format the host name and check if a label exists for it
			$external_website_title = admin_search_get_website_title( str_replace( 'www.', '', strtolower( wp_parse_url( $external_website, PHP_URL_HOST ) ) ) );

			// Build the result array and add it to $return
			$results[ 'external_sites' ][ 'posts' ][] = array(
				'id'				=>	'',
				'edit_post_link'	=>	str_replace( '%q%', urlencode( $q ), $external_website ),

				/* translators: %s: the title of the external website, may be a domain eg. google.com */
				'title'				=>	sprintf( __( 'View results on %s', 'admin-search' ), $external_website_title ),
				'status'			=>	NULL
			);
		}
	}


	// Apply any filters to $results
	$results = apply_filters( 'admin_search_post_results', $results, $q );


	// Ensure attachments are the last results returned
	if ( isset( $results[ 'attachment' ] ) ) {
		$attachments = $results[ 'attachment' ];

		unset( $results[ 'attachment' ] );

		$results[ 'attachment' ] = $attachments;
	}


	// Calculate and return total result count
	$total_results = 0;

	foreach ( $results as $key => $value ) {
		if ( isset( $value[ 'posts' ] ) && is_array( $value[ 'posts' ] ) ) {
			$total_results += count( $value[ 'posts' ] );
		}
	}


	global $wpdb;

	$table_name = $wpdb -> prefix . 'admin_search__searches';


	if ( $total_results > 0 ) {
		// If the query is longer than 8 characters, save it to the `searches` table
		if ( strlen( $q ) > 8 ) {
			// Check if query is in the `searches` table
			if ( $row = $wpdb -> get_row( $wpdb -> prepare( "SELECT * FROM $table_name WHERE query = %s", $q ) ) ) {
				// It is, so update the number of results and increment its occurrences
				$wpdb -> update(
					$table_name,
					[
						'results' => $total_results,
						'occurrences' => (int)$row -> occurrences + 1
					],
					[
						'id' => $row -> id
					]
				);
			} else {
				// It's not, so add it
				$wpdb -> insert(
					$table_name,
					[
						'query' => $q,
						'results' => $total_results,
					]
				);
			}
		}

		// Format and return results
		return admin_search_ajax_return( __( 'Results found', 'admin-search' ), false, $results, $total_results );
	} else {
		// Check if the query is in the `searches` table
		if ( $row = $wpdb -> get_row( $wpdb -> prepare( "SELECT * FROM $table_name WHERE query = %s", $q ) ) ) {
			// It is, so remove it (we don't want queries with no results in the table. If the row exists, it's probably because it had results at one point)
			$wpdb -> delete(
				$table_name,
				[
					'id' => $row -> id
				]
			);
		}

		// Format and return results
		return admin_search_ajax_return( __( 'No results found', 'admin-search' ), false );
	}

}

add_action( 'wp_ajax_admin_search_ajax', 'admin_search_ajax' );
add_action( 'wp_ajax_nopriv_admin_search_ajax', 'admin_search_ajax' );




function admin_search_clear_searches_ajax() {

	// Only logged in users can clear searches
	if ( ! is_user_logged_in() ) {
		return admin_search_ajax_return( __( 'Not authorized', 'admin-search' ) );
	}

	// Only users who can edit other users' posts can clear searches
	if ( ! current_user_can( 'edit_others_pages' ) ) {
		return admin_search_ajax_return( __( 'Not authorized', 'admin-search' ) );
	}

	check_ajax_referer( 'clear-searches' );

	global $wpdb;
	
	$table  = $wpdb -> prefix . 'admin_search__searches';

	$wpdb -> query( "TRUNCATE TABLE $table" );

	return admin_search_ajax_return( __( 'OK', 'admin-search' ) );
	wp_die();

}

add_action( 'wp_ajax_admin_search_clear_searches_ajax', 'admin_search_clear_searches_ajax' );
add_action( 'wp_ajax_nopriv_admin_search_clear_searches_ajax', 'admin_search_clear_searches_ajax' );




/*
 *	Add common plugin fields to searchable fields
 */
add_filter( 'admin_search_meta_queries', function( $fields, $post_type ) {

	// If the `product` type exists add some custom fields to the searchable fields
	if ( 'product' === $post_type ) {
		// Add the `SKU` field from Woocommerce
		$fields[] = '_sku';
	}

	return $fields;

}, 10, 2 );



