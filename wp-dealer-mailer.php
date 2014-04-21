<?php
/**
* The Wordpress Woocommerce Woocommerce Dealer Mailer.
*
* @package   Woocommerce Dealer Mailer
* @author    Raymon Schouwenaar <raymon@raymonschouwenaar.nl>
* @license   GPL-2.0+
* @link      http://www.raymonschouwenaar.nl
* @copyright 2014 Raymon Schouwenaar
*
* Plugin Name: Woocommerce Dealer Mailer
* Plugin URI:  https://github.com/raymonschouwenaar/woocommerce-dealer-mailer
* Description: Send Dealer order email
* Version:     0.0.3
* Author:      Raymon Schouwenaar
* Author URI:  http://www.raymonschouwenaar.nl
* Text Domain: rss_dealer
* License:     GPL-2.0+
* License URI: http://www.gnu.org/licenses/gpl-2.0.txt
* Domain Path: /lang
* License: GPL2 
* Copyright 2014 Raymon Schouwenaar (email : raymon@raymonschouwenaar.nl) This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA */


/**
 * Add the field to the checkout
 * So that an buyer can select his dealer.
 **/
add_action('woocommerce_before_order_notes', 'rss_wcm_dealer_checkout_field');

function rss_wcm_dealer_checkout_field( $checkout ) {

    echo '<div id="order_dealer_field"><h2>'.__('Dealer', 'rss_mailer').'</h2>';

    woocommerce_form_field( 'order_dealer', array(
        'type'          => 'select',
        'class'         => array('order-dealer form-row-wide chzn-select'),
        'input_class'	=> 'chosen_input',
        'label'         => __('Choose your dealer', 'rss_mailer'),
        'placeholder'	=> __('Select dealer', 'rss_mailer'),
        'options'     => rss_wcm_get_dealer_array()
    ), $checkout->get_value( 'order_dealer' ));

    echo '</div>';

}

function rss_wcm_dealer_checkout_custom_select_script() {
	wp_enqueue_script('chosen-select', plugins_url( '/js/chosen.jquery.min.js' , __FILE__ ),array( 'jquery' ));
	wp_enqueue_script('dealer_mailer', plugins_url( '/js/dealer_mailer.jquery.js' , __FILE__ ),array( 'jquery' ));
}

function rss_wcm_dealer_checkout_custom_select_style() {
	wp_register_style( 'chosenCss', plugins_url('/css/chosen.css', __FILE__) );
	wp_enqueue_style( 'chosenCss' );
}

add_action( 'wp_enqueue_scripts', 'rss_wcm_dealer_checkout_custom_select_style' );

add_action( 'wp_enqueue_scripts', 'rss_wcm_dealer_checkout_custom_select_script' );

// Get an list of all locations for the email
function rss_wcm_get_dealer_array() {
	$args = array(
		'order'            => 'ASC',
		'post_type'        => 'sm-location',
		'post_status'      => 'publish',
		'posts_per_page' => -1
	);


	$locations_array = get_posts( $args );
	$posts = array();
	$posts[] = __('Choose your dealer', 'rss_mailer');
	foreach ($locations_array as $post) {
	   $posts[get_post_meta($post->ID,  'location_email', true)] = get_post_meta($post->ID,  'location_city', true).' - '.$post->post_title;
	}
	return $posts;
}

/**
 * Process the checkout
 **/
add_action('woocommerce_checkout_process', 'rss_wcm_dealer_checkout_field_process');

function rss_wcm_dealer_checkout_field_process() {
    global $woocommerce;

    // Check if set, if its not set add an error.
    if (!$_POST['order_dealer'])
         $woocommerce->add_error( __('Unfortunately you have no dealer selected', 'rss_mailer') );
}

/**
 * Update the order meta with field value
 **/
add_action('woocommerce_checkout_update_order_meta', 'rss_wcm_checkout_field_update_order_meta');

function rss_wcm_checkout_field_update_order_meta( $order_id ) {
    if ($_POST['order_dealer']) update_post_meta( $order_id, 'Dealer', esc_attr($_POST['order_dealer']));
}

/**
 * Display field value on the order edition page
 **/
add_action( 'woocommerce_admin_order_data_after_billing_address', 'rss_wcm_field_display_admin_order_meta', 10, 1 );
 
function rss_wcm_field_display_admin_order_meta($order){
    echo '<p><strong>'.__('Dealer', 'rss_mailer').':</strong> ' . $order->order_custom_fields['Dealer'][0] . '</p>';
}

/* 
* Create email template! 
*/
// Triggers for this email
add_action("woocommerce_checkout_order_processed", "rss_send_wcm_dealer_email");

// Function to send an email notification to an dealer.
function rss_send_wcm_dealer_email($order_id, $checkout) {
	global $woocommerce;

	$post = get_post($post_id);
	$checkout = $woocommerce->checkout();
	$order = new WC_Order( $order_id );
	$author = get_userdata($post->post_author);
	$title = get_the_title();

	$order = new WC_Order( $order_id );
	$checkout = $woocommerce->checkout();

	$dealer_email = $checkout->get_value( 'order_dealer' );
	
	$author_email = $dealer_email;
	if($author_email == '') {
		// If the dealer email is empty choose an default email adress
		$author_email = 'info@yourdomain.nl';
		$client_message = __('There is no dealer selected. Contact the client to a dealer.','rss_mailer');
	}

    $site_title = __('Webshop Title','rss_mailer');

    $email_subject = __('New subject title: '.$site_title.'', 'rss_mailer');
    
    $headers = 'From:'.$site_title.' <no-replay@yourdomain.nl>' . "\r\n";
	
	ob_start();
	
	include("email_header.php");
	
	?>
	
		<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

		<p style="color: #333333;"><?php printf( __( 'You have received this order from % s. The order is as follows:', 'rss_mailer' ), $order->billing_first_name . ' ' . $order->billing_last_name ); ?></p>
		<?php echo $client_message; ?>

		<?php do_action( 'woocommerce_email_before_order_table', $order, true ); ?>

		<h2 style="color: #333333;"><?php printf( __( 'Offerte: %s', 'rss_mailer'), $order->get_order_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( woocommerce_date_format(), strtotime( $order->order_date ) ) ); ?>)</h2>

		<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
			<thead>
				<tr>
					<th scope="col" style="text-align:left; border: 1px solid #eee;color: #333333;"><?php _e( 'Product', 'woocommerce' ); ?></th>
					<th scope="col" style="text-align:left; border: 1px solid #eee;color: #333333;"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
					<th scope="col" style="text-align:left; border: 1px solid #eee;color: #333333;"><?php _e( 'Price', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php echo $order->email_order_items_table( false, true ); ?>
			</tbody>
			<tfoot>
				<?php
					if ( $totals = $order->get_order_item_totals() ) {
						$i = 0;
						foreach ( $totals as $total ) {
							$i++;
							?><tr>
								<th scope="row" colspan="2" style="color: #333333;text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
								<td style="text-align:left; color: #333333;border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['value']; ?></td>
							</tr><?php
						}
					}
				?>
			</tfoot>
		</table>

		<?php do_action('woocommerce_email_after_order_table', $order, true); ?>

		<?php do_action( 'woocommerce_email_order_meta', $order, true ); ?>

		<h2 style="color: #333333;"><?php _e( 'Klant details', 'rss_mailer' ); ?></h2>

		<?php if ( $order->billing_email ) : ?>
			<p style="color: #333333;"><strong style="color: #333333;"><?php _e( 'Email:', 'rss_mailer' ); ?></strong> <?php echo $order->billing_email; ?></p>
		<?php endif; ?>
		<?php if ( $order->billing_phone ) : ?>
			<p style="color: #333333;"><strong style="color: #333333;"><?php _e( 'Tel:', 'rss_mailer' ); ?></strong> <?php echo $order->billing_phone; ?></p>
		<?php endif; ?>

		<?php woocommerce_get_template( 'emails/email-addresses.php', array( 'order' => $order ) ); ?>

		<?php do_action( 'woocommerce_email_footer' ); ?>	
	
	<?php
	
	include("email_footer.php");
	
	
	$message = ob_get_contents();
	
	ob_end_clean();
	
	
	// wp_mail($author_email, $email_subject, $message);
	wp_mail($author_email, $email_subject, $message, $headers);
	
}

// Set content to txt/html other you see all the html tags
add_filter('wp_mail_content_type','set_content_type');

function set_content_type($content_type){
return 'text/html';
}

 ?>