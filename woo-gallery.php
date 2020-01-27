<?php
/* 
Plugin Name: Woocommerce Products Gallery
Description: Woocommerce Products Gallery is the perfect solution for any WordPress website which needs more flexible options to display woocommerce products in gallery style. This plugin only works with woocommerce.
Version: 1.0
Author: Rahul Kumar and Gulshan Naz
Author URI: http://www.indianbusybees.com/
License: GPL
Copyright: IndianBusyBees
*/


/* check woocommerce plugin*/
function wpg_plugin_actievate(){
    // Require woocommerce plugin
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) and current_user_can( 'activate_plugins' ) ) {
        // Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires woocommerce plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}
register_activation_hook( __FILE__, 'wpg_plugin_activate' );


/*create custom post type woogallery*/
function wpg_create_posttype_woo_gallery() {
     register_post_type( 'wpgwoogallery',
        array(
            'labels' => array(
                'name' => 'Woo Gallery',
                'singular_name' => 'Image'
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'wpgwoogallery'),
			'supports' => array('title','thumbnail'),
        )
    );
}
add_action( 'init', 'wpg_create_posttype_woo_gallery' );

/*create meta box for woocommerce products used in this post type*/
function wpg_add_your_products_meta_box() {
	add_meta_box(
		'wpg_products', // $id
		'Woo Products', // $title
		'wpg_show_your_products_meta_box', // $callback
		'wpgwoogallery', // $screen
		'normal', // $context
		'high' // $priority
	);
}
add_action( 'add_meta_boxes', 'wpg_add_your_products_meta_box' );

/*show meta box*/
function wpg_show_your_products_meta_box() {
	global $post;  
	$meta = get_post_meta( $post->ID, 'wpg_products', true ); ?>
	<input type="hidden" name="wpg_meta_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">
    <?php 
	$args     = array( 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish' );
	$products = get_posts( $args ); 
	foreach ($products as $k=>$product) { //print_r($product);
		$n = $k + 1;
		?>
		<p><input type="checkbox" name="wpg_products[product_<?php echo $product->ID;?>]" value="<?php echo $product->ID;?>" <?php if(is_array($meta)) {if ($meta["product_".$product->ID] == $product->ID) { echo "checked";}} ?>> <?php echo esc_html($product->post_title);?>
		</p>
	<?php	
	}	
} 

/* save meta box */
function wpg_save_your_products_meta( $post_id ) {   
	// verify nonce
	if ( !wp_verify_nonce( $_POST['wpg_meta_box_nonce'], basename(__FILE__) ) ) {
		return $post_id; 
	}
	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// check permissions
	if ( 'page' === $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}  
	}	
	$old = get_post_meta( $post_id, 'wpg_products', true );
	
	if (isset($_POST['wpg_products']))
		$new = $_POST['wpg_products'];

	if ( $new && $new !== $old ) {
		update_post_meta( $post_id, 'wpg_products', $new);
	} elseif ( '' === $new && $old ) {
		delete_post_meta( $post_id, 'wpg_products', $old);
	}
}
add_action( 'save_post', 'wpg_save_your_products_meta' );

/* include css and js files*/
function wpg_register_plugin_styles() {
	wp_register_style( 'wpg-gallery', plugins_url('css/style.css', __FILE__));
	wp_enqueue_style( 'wpg-gallery' );
	wp_enqueue_script( 'wpg-gallery', plugins_url('js/woo-gallery-js.js', __FILE__), array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'wpg_register_plugin_styles' );

/* shortcode function*/
function wpg_create_woo_gallery_shortcode()
{
	$args     = array( 'post_type' => 'wpgwoogallery', 'posts_per_page' => -1, 'post_status' => 'publish' );
	$gallery = get_posts( $args ); 
	if (sizeof($gallery)>0) {
		$str = '<ul class="wpggallery">';
		foreach ($gallery as $k=>$item) {
			$str .= '<li><a href="javascript:;" data-modal-trigger="trigger-wpg'.$k.'" class="trigger">';
			$str .= get_the_post_thumbnail( $item->ID, 'thumbnail');	
			//$str .= "<h2>".$item->post_title."</h2>";
			$str .= "</a>";
			
			$product_ids = get_post_meta( $item->ID, 'wpg_products', true );
			
			$args     = array( 'post_type' => 'product', 'include' => $product_ids , 'post_status' => 'publish' );
			$products = get_posts( $args ); 
			$pro_str = '<div class="popup-content">';
			$pro_str .= '<ul>';	
			foreach ($products as $product) {
				$pro_str .= "<li>";
				$pro_str .= "<a href='".get_permalink($product->ID)."'>";
				$pro_str .= get_the_post_thumbnail( $product->ID, 'thumbnail');
				$pro_str .= "</a>";
				$pro_str .= '<h3>';
				$pro_str .= "<a href='".get_permalink($product->ID)."'>";
				$pro_str .= $product->post_title;
				$pro_str .= "</a>";
				$pro_str .= '</h3>';
				$pro_str .= "</a>";
				$_product = wc_get_product( $product->ID );
				$pro_str .= '<div class="price">';
				$pro_str .= wc_price($_product->get_price());
				$pro_str .= '</div>';
				$pro_str .= "</li>";				
			}
			$pro_str .= '</ul></div>';
			
			
			$str .= '<div data-modal="trigger-wpg'.$k.'" class="wpgmodal">
				  <article class="content-wrapper">
					<button class="close"></button>
					<div class="content">
					  <p>'.$pro_str.'</p>
					</div>
					</article>
				</div>';			
			$str .= "</li>";
		}
		$str .= "</ul>";
		}
	return $str;
}
add_shortcode('wpg_gallery', 'wpg_create_woo_gallery_shortcode');
?>