<?php
/**
 * Events
 *
 * Create a "Events" page in WordPress using 
 * custom post types, taxonomies, and Advanced Custom
 * Fields
 */
if ( !class_exists('Events') ):

class Events
{
	/**
	 * Initialize & hook into WP
	 */
	public function __construct() {
		add_action( 'init', array($this, 'register_post_type'), 0 );
		add_action( 'init', array($this, 'register_taxonomy'), 0 );
		add_action( 'wp_enqueue_scripts', array($this, 'load_styles'), 101 );
		add_action( 'admin_notices', array($this, 'admin_notice') );
		add_action( 'after_setup_theme', array($this, 'after_setup_theme') );
	}
	
	
	/**
	 * Dependencies check
	 *
	 * Check to make sure we have the required plugin(s) 
	 * installed.
	 */
	public function dependencies_check() {
	   return ( is_plugin_active('advanced-custom-fields/acf.php') ) ? true : false;
	}
	
	
	/**
	 * Load CSS for template-event.php
	 */
	public function load_styles() {
		if ( is_page_template('events-template.php') )
	   	wp_enqueue_style( 'events-template', get_stylesheet_directory_uri() . '/lib/events/events.css' );
	}
	
	
	/**
	 * Theme setup
	 *
	 * Create a custom thumbnail size for our Events
	 */
	public function after_setup_theme() {
	  add_image_size('event-thumb', 100, 100, true); // 100px x 100px with hard crop enabled
	}
	
	
	/**
	 * Dependencies notifications
	 *
	 * Required plugin isn't installed, notify user
	 */
	public function admin_notice() {
	
		// Check for required plugins
		if ( $this->dependencies_check() )
			return;
		
		// Display message
		$install_link = admin_url('plugin-install.php?tab=search&type=term&s=Advanced+Custom+Fields&plugin-search-input=Search+Plugins');
		$html =  '<div class="error"><p>';
		$html .= '<strong>Events</strong> needs the <a href="http://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields</a> plugin to work. Please <a href="' . $install_link . '">install it now</a>.';
		$html .= '</p></div>';
		
		echo $html;
	}


	/**
	 * Register post type
	 */
	public function register_post_type() {
	   
	   // Labels
		$labels = array(
			'name' => _x("events", "post type general name"),
			'singular_name' => _x("Event", "post type singular name"),
			'menu_name' => 'Events',
			'add_new' => _x("Add New", "event item"),
			'add_new_item' => __("Add New Event"),
			'edit_item' => __("Edit Event"),
			'new_item' => __("New Event"),
			'view_item' => __("View Event"),
			'search_items' => __("Search Events"),
			'not_found' =>  __("No Events Found"),
			'not_found_in_trash' => __("No Events Found in Trash"),
			'parent_item_colon' => ''
		);
		
		// Register post type
		register_post_type('events' , array(
			'labels' => $labels,
			'public' => true,
			'has_archive' => false,
			'menu_icon' => get_stylesheet_directory_uri() . '/lib/events/events-icon.png',
			'rewrite' => false,
			'supports' => array('title', 'editor', 'thumbnail')
		) );
	}
	
	
	/**
	 * Register 'City' taxonomy
	 */
	public function register_taxonomy() {
		
		// Labels
		$singular = 'City';
		$plural = 'Cities';
		$labels = array(
			'name' => _x( $plural, "taxonomy general name"),
			'singular_name' => _x( $singular, "taxonomy singular name"),
			'search_items' =>  __("Search $singular"),
			'all_items' => __("All $singular"),
			'parent_item' => __("Parent $singular"),
			'parent_item_colon' => __("Parent $singular:"),
			'edit_item' => __("Edit $singular"),
			'update_item' => __("Update $singular"),
			'add_new_item' => __("Add New $singular"),
			'new_item_name' => __("New $singular Name"),
		);

		// Register and attach to 'events' post type
		register_taxonomy( strtolower($singular), 'events', array(
			'public' => true,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'hierarchical' => true,
			'query_var' => true,
			'rewrite' => false,
			'labels' => $labels
		) );
	}
	
	
	/**
	 * Display the cached HTML
	 */
	static function display() {
	
		global $post;
	
		// Caching, re-run query if not found or expired
		$transient_label = __CLASS__ . "_" . __FUNCTION__; // Transient label will be 'Events_display'
		if ( false === ( $html = get_transient($transient_label) ) ) {

		   // Get 'event' posts
			$Events = get_posts( array(
				'post_type' => 'events',
				'posts_per_page' => 50, // Unlimited posts
				'orderby' => 'title', // Order alphabetically by name
				'order' => 'ASC' // Start with 'A'
			) );
			
			$html = null;
			if ( $Events ):
			
			// Gather output
		   ob_start();
			?>
			<section class="row events">
				<div class="intro">
					<h2>Bars</h2>
					<p class="lead">&ldquo;Below are a list of great bar venues location in the heart of Liverpool City Centre.</p>
				</div>
				
				<?php 
				foreach ( $Events as $post ): 
				setup_postdata($post);
				
				// Resize and CDNize thumbnails using Automattic Photon service
				$thumb_src = null;
				if ( has_post_thumbnail($post->ID) ) {
					$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'event-thumb' );
					$thumb_src = $src[0];
				}
				?>

				<article>
				
				<?php the_content(); ?>

					 <div class="col-sm-2 col-md-2">
	         			 	<?php if ( $thumb_src ): ?>
								<img src="<?php echo $thumb_src; ?>" alt="<?php the_title(); ?>, <?php the_field('team_position'); ?>" class="img-rounded img-responsive">
							<?php endif; ?>
        			</div>
					
					<div class="profile-content">
						<h3><?php the_title(); ?></h3>
						<p class="lead position"><?php the_field('team_position'); ?></p>
						<?php the_content(); ?>
					</div>
					
					<div class="profile-footer">
						<a href="tel:<?php the_field('team_phone'); ?>"><i class="icon-mobile-phone"></i></a>
						<a href="mailto:<?php echo antispambot( get_field('team_email') ); ?>"><i class="icon-envelope"></i></a>
						<?php if ( $twitter = get_field('team_twitter') ): ?>
						<a href="<?php echo $twitter; ?>"><i class="icon-twitter"></i></a>
						<?php endif; ?>
						<?php if ( $linkedin = get_field('team_linkedin') ): ?>
						<a href="<?php echo $linkedin; ?>"><i class="icon-linkedin"></i></a>
						<?php endif; ?>
					</div>
				</article><!-- /.profile -->
				<?php endforeach; ?>
			</section><!-- /.row -->
			<?php 
			// Save output
		   $html = ob_get_contents();
		   ob_end_clean();
		   
			endif; // end if $events

		   // Store output in cache
		   set_transient( $transient_label, $html, DAY_IN_SECONDS );
		}
		
		// Output the HTML if it exists
		return ( $html ) ? $html : false;
	}
}

$Events = new Events();

endif;