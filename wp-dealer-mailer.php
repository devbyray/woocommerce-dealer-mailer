<?php
/**
* The Wordpress Woocommerce Woocommerce Dealer Mailer.
*
* @package   Woocommerce Dealer Mailer
* @author    Web Media Helden <raymon@webmediahelden.nl>
* @license   GPL-2.0+
* @link      http://www.webmediahelden.nl
* @copyright 2013 Web Media Helden
*
* Plugin Name: Woocommerce Dealer Mailer NL
* Plugin URI:  http://www.webmediahelden.nl
* Description: Send Dealer order email
* Version:     0.0.2
* Author:      Web Media Helden
* Author URI:  http://www.webmediahelden.nl
* Text Domain: wmh_dealer
* License:     GPL-2.0+
* License URI: http://www.gnu.org/licenses/gpl-2.0.txt
* Domain Path: /lang
* License: GPL2 
* Copyright 2013 Web Media Helden (email : raymon@webmediahelden.nl) This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA */


/**
 * Add the field to the checkout
 * So that an buyer can select his dealer.
 **/
add_action('woocommerce_before_order_notes', 'wmh_wcm_dealer_checkout_field');

function wmh_wcm_dealer_checkout_field( $checkout ) {

    echo '<div id="order_dealer_field"><h2>'.__('Dealer', 'bewe_mailer').'</h2>';

    woocommerce_form_field( 'order_dealer', array(
        'type'          => 'select',
        'class'         => array('order-dealer form-row-wide chzn-select'),
        'input_class'	=> 'chosen_input',
        'label'         => __('Kies uw dealer', 'bewe_mailer'),
        'placeholder'	=> __('Selecteer', 'bewe_mailer'),
        'options'     => wmh_wcm_get_dealer_array()
    ), $checkout->get_value( 'order_dealer' ));

    echo '</div>';

}

function wmh_wcm_dealer_checkout_custom_select_script() {
	wp_enqueue_script('chosen-select', plugins_url( '/js/chosen.jquery.min.js' , __FILE__ ),array( 'jquery' ));
	wp_enqueue_script('dealer_mailer', plugins_url( '/js/dealer_mailer.jquery.js' , __FILE__ ),array( 'jquery' ));
}

function wmh_wcm_dealer_checkout_custom_select_style() {
	wp_register_style( 'chosenCss', plugins_url('/css/chosen.css', __FILE__) );
	wp_enqueue_style( 'chosenCss' );
}

add_action( 'wp_enqueue_scripts', 'wmh_wcm_dealer_checkout_custom_select_style' );

add_action( 'wp_enqueue_scripts', 'wmh_wcm_dealer_checkout_custom_select_script' );

// Get an list of all locations for the email
function wmh_wcm_get_dealer_array() {
	$args = array(
		'order'            => 'ASC',
		'post_type'        => 'sm-location',
		'post_status'      => 'publish',
		'posts_per_page' => -1
	);


	$locations_array = get_posts( $args );
	$posts = array();
	$posts[] = __('Selecteer een dealer', 'bewe_mailer');
	foreach ($locations_array as $post) {
	   $posts[get_post_meta($post->ID,  'location_email', true)] = get_post_meta($post->ID,  'location_city', true).' - '.$post->post_title;
	}
	return $posts;
}

/**
 * Process the checkout
 **/
add_action('woocommerce_checkout_process', 'wmh_wcm_dealer_checkout_field_process');

function wmh_wcm_dealer_checkout_field_process() {
    global $woocommerce;

    // Check if set, if its not set add an error.
    if (!$_POST['order_dealer'])
         $woocommerce->add_error( __('Helaas u heeft geen dealer geselecteerd', 'bewe_mailer') );
}

/**
 * Update the order meta with field value
 **/
add_action('woocommerce_checkout_update_order_meta', 'wmh_wcm_checkout_field_update_order_meta');

function wmh_wcm_checkout_field_update_order_meta( $order_id ) {
    if ($_POST['order_dealer']) update_post_meta( $order_id, 'Dealer', esc_attr($_POST['order_dealer']));
}

/**
 * Display field value on the order edition page
 **/
add_action( 'woocommerce_admin_order_data_after_billing_address', 'wmh_wcm_field_display_admin_order_meta', 10, 1 );
 
function wmh_wcm_field_display_admin_order_meta($order){
    echo '<p><strong>'.__('Dealer', 'bewe_mailer').':</strong> ' . $order->order_custom_fields['Dealer'][0] . '</p>';
}


// Custom breadcrumbs
function wmh_woocommerce_breadcrumbs() {
    return array(
            'delimiter'   => ' <span class="bread_break">&#47</span> ',
            'wrap_before' => '<nav class="woocommerce-breadcrumb" itemprop="breadcrumb">',
            'wrap_after'  => '</nav>',
            'before'      => '<span class="bread_element">',
            'after'       => '</span>',
            'home'        => _x( 'Home', 'breadcrumb', 'woocommerce' ),
        );
}

add_filter( 'woocommerce_breadcrumb_defaults', 'wmh_woocommerce_breadcrumbs' );


/* 
* Create email template! 
*/
// Triggers for this email
add_action("woocommerce_checkout_order_processed", "wmh_send_wcm_dealer_email");

// Function to send an email notification to an dealer.
function wmh_send_wcm_dealer_email($order_id, $checkout) {
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
		$author_email = 'info@webmediahelden.nl';
		$client_message = __('Er is geen dealer geselecteerd. Contact de klant voor een dealer.','bewe_mailer');
	}

    $site_title = __('Bewe Vloerbedekking','bewe_mailer');

    $email_subject = __('Nieuwe offerte aanvraag van: '.$site_title.'', 'bewe_mailer');
    
    $headers = 'From:'.$site_title.' <no-replay@bewe.nl>' . "\r\n";
	
	ob_start();
	
	include("email_header.php");
	
	?>
	
		<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

		<p style="color: #333333;"><?php printf( __( 'U heeft deze order ontvangen van %s. De order is als volgt:', 'bewe_mailer' ), $order->billing_first_name . ' ' . $order->billing_last_name ); ?></p>
		<?php echo $client_message; ?>

		<?php do_action( 'woocommerce_email_before_order_table', $order, true ); ?>

		<h2 style="color: #333333;"><?php printf( __( 'Offerte: %s', 'bewe_mailer'), $order->get_order_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( woocommerce_date_format(), strtotime( $order->order_date ) ) ); ?>)</h2>

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

		<h2 style="color: #333333;"><?php _e( 'Klant details', 'bewe_mailer' ); ?></h2>

		<?php if ( $order->billing_email ) : ?>
			<p style="color: #333333;"><strong style="color: #333333;"><?php _e( 'Email:', 'bewe_mailer' ); ?></strong> <?php echo $order->billing_email; ?></p>
		<?php endif; ?>
		<?php if ( $order->billing_phone ) : ?>
			<p style="color: #333333;"><strong style="color: #333333;"><?php _e( 'Tel:', 'bewe_mailer' ); ?></strong> <?php echo $order->billing_phone; ?></p>
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

// If is search, an there is 1 product as result the redirect will be disabled
add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );


/**remove wooocommerce's own login processing to replace with our own to enable redeirection by role */
remove_action('init','woocommerce_process_login');

add_filter( 'woocommerce_process_login', 'unset_user_redirect');

function unset_user_redirect($redirect, $user){
	$redirect = '';
	$user = '';
	return $redirect;
	return $user;
}

 ?>