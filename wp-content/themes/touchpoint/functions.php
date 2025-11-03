<?php
	/**
	* Theme functions and definitions
	*
	* @package TouchpointCRM
	*/
	


	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly.
	}
	
	define( 'TOUCHPOINT_VERSION', '1.0.0' );
	
	if ( ! isset( $content_width ) ) {
		$content_width = 800; // Pixels.
	}
	
	if ( ! function_exists( 'touchpointcrm_setup' ) ) {
		/**
		* Set up theme support.
		*/
		function touchpointcrm_setup() {
			
			// Register navigation menus.
			register_nav_menus( [
				'primary' => esc_html__( 'Primary Menu', 'touchpointcrm' ),
				'footer'  => esc_html__( 'Footer Menu', 'touchpointcrm' ),
			] );
			
			// Theme support options.
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support( 'html5', [
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'script',
				'style',
			] );
			add_theme_support( 'custom-logo', [
				'height'      => 100,
				'width'       => 350,
				'flex-height' => true,
				'flex-width'  => true,
			] );
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );
			add_theme_support( 'editor-styles' );
			add_editor_style( 'editor-styles.css' );
			
			// WooCommerce support
			add_theme_support( 'woocommerce' );
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
			
			add_theme_support('acf-blocks');

		}
	}
	
	add_action( 'after_setup_theme', 'touchpointcrm_setup' );
	

// Re-enable default WordPress category meta box (useful for ACF taxonomy fields)
	add_filter('acf/settings/remove_wp_meta_box', '__return_false');

// Re-enable default WordPress category meta box (useful for ACF taxonomy fields)
	add_filter('acf/load_field/name=main_category', function( $field ) {
		$categories = get_categories( ['hide_empty' => false] );
		$choices = [];
		
		foreach( $categories as $cat ) {
			$choices[$cat->term_id] = $cat->name;
		}
		
		$field['choices'] = $choices;
		return $field;
	});


/* Custom Taxonomy */

	function create_content_type_taxonomy() {
	register_taxonomy(
		'content_type',
		'post',
		array(
			'label' => __('Content Type', 'touchpointcrm'),
			'rewrite' => array('slug' => 'content-type'),
			'hierarchical' => false,
			'show_admin_column' => true,
		)
	);
}
add_action('init', 'create_content_type_taxonomy');

/* Shortcode for excerpt with toggle and word limit */

	if ( ! shortcode_exists( 'styled_excerpt' ) ) {
		add_shortcode( 'styled_excerpt', function() {
			$full_excerpt = get_the_excerpt();
			$word_limit = 30;
			
			$words = explode( ' ', $full_excerpt );
			$short_excerpt = $full_excerpt;
			
			if ( count( $words ) > $word_limit ) {
				$short_excerpt = implode( ' ', array_slice( $words, 0, $word_limit ) ) . '…';
			}
			
			return '
		<div class="excerpt-wrapper">
			<strong>' . esc_html__( 'Summary.', 'touchpointcrm' ) . '</strong>
			<span class="excerpt-text" data-full="' . esc_attr( $full_excerpt ) . '">' . esc_html( $short_excerpt ) . '</span>
			<a href="javascript:void(0);" class="excerpt-toggle" onclick="toggleExcerpt(this)"><em><strong>More</strong></em></a>
		</div>
		';
		} );
	}

/* DEque Hello and enque Touch */
	
	function redirect_hello_elementor_assets() {
		if (is_child_theme()) {
			// Dequeue Hello Elementor's main.js
			wp_dequeue_script('hello-elementor-main-js'); 
			
			// Enqueue TouchpointCRM's main.js instead
			wp_enqueue_script('touchpointcrm-main-js', get_stylesheet_directory_uri() . '/js/main.js', [], null, true);
		}
	}
	add_action('wp_enqueue_scripts', 'redirect_hello_elementor_assets', 11);


	function touchpointcrm_enqueue_styles() {
		// Dequeue Hello Elementor's style.css
		wp_dequeue_style('hello-elementor-style'); 
		
		// Enqueue TouchpointCRM's style.css
		wp_enqueue_style(
			'touchpointcrm-style',
			get_stylesheet_uri(),
			[],
			filemtime(get_stylesheet_directory() . '/style.css'),
			'all'
		);
	}
		
	add_action('wp_enqueue_scripts', 'touchpointcrm_enqueue_styles', 10); // Priority 10, load first
	
/* Load DM-Sans */
	function touchpointcrm_fonts() {
		// Enqueue the necessary fonts from Google Fonts
		wp_enqueue_style( 'barlow-font', 'https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;600&display=swap', false );
		wp_enqueue_style( 'dm-sans-font', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&display=swap', false );
		wp_enqueue_style( 'inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap', false );
	}
	add_action( 'wp_enqueue_scripts', 'touchpointcrm_fonts',  5 ); // Priority 5, load after theme styles

/* ACF abstract block */
	add_action('acf/init', 'register_abstract_block');
	function register_abstract_block() {
		if( function_exists('acf_register_block_type') ) {
			acf_register_block_type(array(
				'name'              => 'abstract',
				'title'             => __('Abstract Block'),
				'description'       => __('Executive summary content for gated content'),
				'render_template'   => 'template-parts/blocks/abstract/abstract.php',
				'category'          => 'formatting',
				'icon'              => 'excerpt-view',
				'mode'              => 'edit',
				'keywords'          => array( 'abstract', 'summary', 'overview' ),
				'supports'          => [ 'align' => false ]
			));
		}
	}

/* ACF abstract block short code */
	function render_abstract_shortcode() {
		ob_start();
		get_template_part('template-parts/blocks/abstract/abstract');
		return ob_get_clean();
	}
	add_shortcode('abstract_block', 'render_abstract_shortcode');

/* ACF Footnote block */	
	add_action('acf/init', function() {
		if( function_exists('acf_register_block_type') ) {
			acf_register_block_type([
				'name'            => 'footnotes',
				'title'           => __('Footnotes'),
				'render_template' => 'template-parts/blocks/footnotes/footnotes.php',
				'category'        => 'formatting',
				'icon'            => 'editor-ol',
				'mode'            => 'edit',
			]);
		}
	});

/* Footnotes block short code */
	function render_footnotes_shortcode() {
		ob_start();
		get_template_part('template-parts/blocks/footnotes/footnotes');
		return ob_get_clean();
	}
	add_shortcode('footnotes_block', 'render_footnotes_shortcode');

	

/* Post-Meta Block */
	function render_post_meta_block($atts) {
		if (!function_exists('get_field')) {
			return '<!-- ACF not available -->';
		}
		
		$atts = shortcode_atts([
			'show' => 'category,title,author,date',
		], $atts, 'post_meta_block');
		
		$show = explode(',', $atts['show']);
		$html = '<div class="post-meta">';
		
		// CATEGORY
		if (in_array('category', $show)) {
			$term = get_field('lead_category');
			if ($term instanceof WP_Term) {
				$html .= '<div class="lead-category"><a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a></div>';
			}
		}
		
		// TITLE
		if (in_array('title', $show)) {
			$html .= '<h1 class="post-title">' . esc_html(get_the_title()) . '</h1>';
		}
		
		// AUTHOR(S)
		if (in_array('author', $show)) {
			$authors = get_field('authors');
			if (!empty($authors)) {
				$names = array_map(function($post) {
					return get_the_title($post);
				}, $authors);
				
				if (count($names) > 2) {
					$last = array_pop($names);
					$authors_string = implode(', ', $names) . ', and ' . $last;
				} else {
					$authors_string = implode(' and ', $names);
				}
				
				$html .= '<div class="author-meta">By ' . esc_html($authors_string) . '</div>';
			}
		}
		
		// DATE
		if (in_array('date', $show)) {
			$date = get_the_date();
			$html .= '<div class="meta-date">' . esc_html($date) . '</div>';
		}
		
		$html .= '</div>'; // Close .post-meta
		return $html;
	}
	add_shortcode('post_meta_block', 'render_post_meta_block');


/* Fuck Grammerly*/
	add_action( 'admin_enqueue_scripts', function() {
		if ( is_admin() ) {
			wp_dequeue_script( 'grammarly' );
			wp_dequeue_script( 'grammarly-extension' );
		}
	}, 100 );
	
	
	add_action('shutdown', function() {
		if ( defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator') ) {
			@file_put_contents( WP_CONTENT_DIR . '/debug.log', '' );
		}
	});

/* Elementor Ads Quick Test*/
	add_shortcode('kh_test_slots', function() {
		ob_start();
		$slots = [
			'exit_overlay',
			'footer',
			'header',
			'popup',
			'sidebar1',
			'sidebar2',
			'ticker',
			'slide_in',
		];
		
		foreach ($slots as $slot) {
			echo "<div style='border:1px dashed #ccc;margin:10px;padding:10px;'>";
			echo "<h4>Slot: $slot</h4>";
			kh_ad_manager_render_ad_for_slot_in_context($slot);
			echo "</div>";
		}
		return ob_get_clean();
	});
