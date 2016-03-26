<?php
/**
 * Plugin Name: Another Staff List Plugin
 * Plugin URI: http://www.thejameswilliam.com
 * Description: List Staff Members by Department and creates a staff member page that integrates with your theme. 
 * Version: 0.1.0
 * Author: James W. Johnson
 * Author URI: http://www.thejameswilliam.com
 * License: GPL2
 */
 


/*
|--------------------------------------------------------------------------
| ACTIVATION
|--------------------------------------------------------------------------
*/


//Make sure we have the advanced custom fields plugin installed and activated.
register_activation_hook( __FILE__, 'child_plugin_activate' );
function child_plugin_activate(){

    // Require parent plugin
    if ( ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) and current_user_can( 'activate_plugins' ) ) {
        // Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires the Advanced Custom Fields Pro plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}


//Add a couple of image sizes to use. 
add_action( 'init', 'jwj_add_new_image_sizes' );
function jwj_add_new_image_sizes() {
    add_image_size( 'headshot', 200, 200, true ); 
	add_image_size( 'staff_page', 500, 250, true ); 
}



//Replace the_content(); on all pages when out custom post type of jwj_staff is called.
function replace_content_with_plugin($content) {
	global $post;
	$id = $post->ID;
	
	if($post->post_type == "jwj_staff") {
		$image = get_field('headshot');
		$size = 'staff_page';
		$bio = get_field('bio');
		$image = wp_get_attachment_image( $image, $size );
		$quote = get_field('personal_quote');
		
		$image = '<div class="staff_image">' . $image . '</div>';
		$quote = '<div class="staff_quote">' . $quote . '</div>';
		$bio = '<div class="staff_bio">' . $bio . '</div>';
		
		$content = $image . $quote . $bio;
		
		return $content;
	}
	return $content;
}

add_filter('the_content', 'replace_content_with_plugin');




/*
|--------------------------------------------------------------------------
| POST TYPES
|--------------------------------------------------------------------------
*/



add_action( 'init', 'create_jwj_staff_post_type' );
// Create 1 Custom Post type for classes, called jwj_staff
function create_jwj_staff_post_type()
{
   
	register_post_type('jwj_staff', // Register Custom Post Type
        array(
        'labels' => array(
            'name' => __('Staff Members', 'jwj_staff'), // Rename these to suit
            'singular_name' => __('Staff Member', 'jwj_staff'),
            'add_new' => __('Add More Staff', 'jwj_staff'),
            'add_new_item' => __('Add More Staff', 'jwj_staff'),
            'edit' => __('Edit Staff', 'jwj_staff'),
            'edit_item' => __('Edit Staff', 'jwj_staff'),
            'new_item' => __('New Staff Member', 'jwj_staff'),
            'view' => __('View Staff Members', 'jwj_staff'),
            'view_item' => __('View Staff Members', 'jwj_staff'),
            'search_items' => __('Search Staff Members', 'jwj_staff'),
            'not_found' => __('No Staff Members found', 'jwj_staff'),
            'not_found_in_trash' => __('No Staff Members found in Trash', 'jwj_staff')
        ),
        'public' => true,
        'hierarchical' => true, // Allows your posts to behave like Hierarchy Pages
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail'
        ), // Go to Dashboard Custom HTML5 Blank post for supports
        'can_export' => true, // Allows export in Tools > Export
        'taxonomies' => array(
            'location',
            'style',
			'day'
        ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',// Add Category and Post Tags support
    ));
	
}




/*
|--------------------------------------------------------------------------
| TAXONOMIES
|--------------------------------------------------------------------------
*/

// hook into the init action and call create_staff_taxonomies when it fires
add_action( 'init', 'create_staff_taxonomies', 0 );

// create  taxonomies for the post type jwj_staff
function create_staff_taxonomies() {
	// Add new taxonomy, styles for dance classes
	$labels = array(
		'name'              => _x( 'Staff Departments', 'taxonomy general name' ),
		'singular_name'     => _x( 'Staff Department', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Staff Departments' ),
		'all_items'         => __( 'All Staff Departments' ),
		'parent_item'       => __( 'Parent Staff Department' ),
		'parent_item_colon' => __( 'Parent Staff Department:' ),
		'edit_item'         => __( 'Edit Staff Department' ),
		'update_item'       => __( 'Update Staff Department' ),
		'add_new_item'      => __( 'Add New Staff Department' ),
		'new_item_name'     => __( 'New Staff Department' ),
		'menu_name'         => __( 'Staff Departments' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => false,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'jwj_department' ),
	);

	register_taxonomy( 'jwj_department', array( 'jwj_staff' ), $args );

	
	
	
}



/*
|--------------------------------------------------------------------------
| ASSETS
|--------------------------------------------------------------------------
*/


add_action( 'wp_enqueue_scripts', 'jwj_staff_enqueued_assets' );

function jwj_staff_enqueued_assets() {
	
	wp_register_style('jwj-staff-css-styles', plugin_dir_url( __FILE__ ) . '/styles/style.css', array(), '1.0', 'all');
    wp_enqueue_style('jwj-staff-css-styles'); // Enqueue it!
};

add_action('wp_footer', 'jwj_add_fonts');
function jwj_add_fonts() {
	echo '<link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet" type="text/css">';	
};




/*
|--------------------------------------------------------------------------
| SHORTCODES
|--------------------------------------------------------------------------
*/

add_shortcode('jwj_stafflist', 'jwj_list_staff_by_department');


function jwj_list_staff_by_department(){

	echo '<br>';
	
	$taxonomy = 'jwj_department';
	$departments = get_terms($taxonomy,
					array(
						'orderby'    => 'count',
						'hide_empty' => 1,
						'order' => 'DESC',
						)
					);
	foreach( $departments as $department ) {
			$args = array(
				  'post_status' => 'published',
				  'post_type' => 'jwj_staff',
				  'orderby' => 'title',
				  'tax_query' => array(
						array (
							'taxonomy' => $taxonomy,
							'field' => 'ID',
							'terms' => $department->term_id,
							
						),
					
					),
			);
			$jwj_posts = new WP_Query($args);
			
			echo '<h1>';
			echo $department->name;
			echo '</h1>';
			
			if($jwj_posts->have_posts()) : 
            $count = 0;
			?>
			<div class="jwj_staff_list">
			
			   <?php while($jwj_posts->have_posts()) : $jwj_posts->the_post();
				$image = get_field('headshot');
				$size = 'headshot';
				$link = get_permalink();
				$count ++;
			   ?>
			   
				  <a href="<?php echo $link ?>">
				  <div class="staff_member">
					
					  <div class="staff_headshot">
						 <?php if( $image ) {
							echo wp_get_attachment_image( $image, $size );
						} ?>
					  </div>
					  <div class="staff_name">
						 <?php the_title(); ?>
					  </div>
                      <?php if (get_field('title')) { ?>
                          <div class="staff_title">
                             <?php the_field('title') ?>
                          </div>
                      <?php }; ?>
					  <div class="staff_quote">
						 <?php
						 if (the_field('personal_quote')) {
							 echo '"';
								echo the_field('personal_quote');
							 echo '"';
						 };
						 ?>
					  </div>
				  </div>
                  <?php 
				  	if($count === 3) {
					  echo '<div class="jwj_clear_three"></div>';
					  $count = 0;
					} elseif ($count === 2) {
					  echo '<div class="jwj_clear_two"></div>';
					  $count = 0;
					}; 
					
					
					?>
				  </a>
			   <?php endwhile ?>
			   <?php wp_reset_postdata(); ?>
			
			</div>
			
			<?php endif;
			echo '<br>';
			echo '<hr class="jwj_clear">';
			
	};
};


add_shortcode('jwj_staffmember', 'jwj_list_staff_member');


function jwj_list_staff_member($ID){

	extract(shortcode_atts(array(
        'id' => 'id'
    ), $ID));
	
	$ID = $ID['id'];
	
	$args = array(
		  'post_status' => 'published',
		  'post_type' => 'jwj_staff',
		  'p' => $ID,
		);
	$jwj_posts = new WP_Query($args);
	?>
	
	<?php if($jwj_posts->have_posts()) : ?>
	<div class="jwj_staff_list">
	
	   <?php while($jwj_posts->have_posts()) : $jwj_posts->the_post();
       	$image = get_field('headshot');
		$size = 'headshot';
		$link = get_permalink();
       ?>
       
          <a href="<?php echo $link ?>">
       	  <div class="staff_member">
          	
              <div class="staff_headshot">
                 <?php if( $image ) {
                    echo wp_get_attachment_image( $image, $size );
                } ?>
              </div>
              <div class="staff_name">
                 <?php the_title() ?>
              </div>
              
              
              <?php if (get_field('title')) { ?>
                  <div class="staff_title">
                     <?php the_field('title') ?>
                  </div>
              <?php }; ?>
              
              
              <div class="staff_quote">
                 <?php
				 if (the_field('personal_quote')) {
					 echo '"';
						echo the_field('personal_quote');
					 echo '"';
				 };
				 ?>
              </div>
          </div>
          </a>
	   <?php endwhile ?>
       <?php wp_reset_postdata(); ?>
	
    </div>
	
	<?php endif;


}


/*
|--------------------------------------------------------------------------
| CUSTOM FIELDS
|--------------------------------------------------------------------------
*/
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array (
	'key' => 'group_56f05dc341f1e',
	'title' => 'Staff Custom Fields',
	'fields' => array (
		array (
			'key' => 'field_56f05dc5736e6',
			'label' => 'Bio',
			'name' => 'bio',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'maxlength' => '',
			'rows' => '',
			'new_lines' => 'wpautop',
			'readonly' => 0,
			'disabled' => 0,
		),
		array (
			'key' => 'field_56f05de9736e7',
			'label' => 'Personal Quote',
			'name' => 'personal_quote',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
			'readonly' => 0,
			'disabled' => 0,
		),
		array (
			'key' => 'field_56f081de098e8',
			'label' => 'Title',
			'name' => 'title',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
			'readonly' => 0,
			'disabled' => 0,
		),
		array (
			'key' => 'field_56f05e05736e8',
			'label' => 'Headshot',
			'name' => 'headshot',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'id',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
	),
	'location' => array (
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'jwj_staff',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array (
		0 => 'the_content',
		1 => 'excerpt',
		2 => 'discussion',
		3 => 'comments',
		4 => 'featured_image',
	),
	'active' => 1,
	'description' => '',
));

endif;