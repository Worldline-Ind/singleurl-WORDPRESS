<?php 
/*
Plugin Name: Paynimo
Plugin URI: 
Description: Extends WooCommerce with paynimo gateway.
Version: 1.2
Author: Sadhana Dhande
Author URI: 

Copyright: 
License: 
License URI: 
*/
require_once __DIR__.'/includes/TransactionRequestBean.php';
require_once __DIR__.'/includes/TransactionResponseBean.php';
if ( ! defined( 'ABSPATH' ) )
	exit;
add_action('plugins_loaded', 'woocommerce_paynimo_init', 0);

function woocommerce_paynimo_init(){
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	class woocommerce_paynimo extends WC_Payment_Gateway{
		
		public function __construct(){
			global $woocommerce;
			$this->id           = 'paynimo';
			$this->method_title = __('Paynimo', 'paynimo');
			$this->icon         =  plugins_url( 'images/paynimo.png' , __FILE__ );
			$this->has_fields   = false;			
			
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->settings['title'];
			$this->description      = $this->settings['description'];
			$this->paynimo_merchant_code      = $this->settings['paynimo_merchant_code'];
			$this->paynimo_request_type      = $this->settings['paynimo_request_type'];
			$this->paynimo_key      = $this->settings['paynimo_key'];
			$this->paynimo_iv      = $this->settings['paynimo_iv'];
			$this->paynimo_webservice_locator      = $this->settings['paynimo_webservice_locator'];
			$this->paynimo_merchant_scheme_code      = $this->settings['paynimo_merchant_scheme_code'];
			$this->paynimo_redirect_msg      = $this->settings['paynimo_redirect_msg'];
			$this->paynimo_decline_msg      = $this->settings['paynimo_decline_msg'];
			$this->paynimo_success_msg      = $this->settings['paynimo_success_msg'];
			$this->paynimo_hashalgo      = $this->settings['paynimo_hashalgo'];
					
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			if($this->paynimo_webservice_locator == 'Test'){
				$this->liveurl  = 'https://www.tpsl-india.in/PaymentGateway/TransactionDetailsNew.wsdl';
			}else{
				$this->liveurl  = 'https://www.tpsl-india.in/PaymentGateway/TransactionDetailsNew.wsdl';
			}
			$this->notify_url = add_query_arg( 'wc-api', 'woocommerce_paynimo', home_url('/'));
			$this->msg['message'] = "";
			$this->msg['class']   = "";
			
			add_action('woocommerce_api_woocommerce_paynimo', array( $this, 'check_paynimo_response' ) );
			add_action('valid-paynimo-request', array($this, 'successful_request'));
			
			add_action('woocommerce_receipt_paynimo', array($this, 'receipt_page'));
			// add_action('woocommerce_thankyou_paynimo',array($this, 'thankyou_page'));
			
			// $this->notify_url = add_query_arg('wc-api', 'WC_worldline', home_url('/'));
			// $this->msg['message'] = "";
			// $this->msg['class']   = "";

			// add_action('woocommerce_api_wc_worldline', array($this, 'check_paynimo_response'));
			// add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_merchantid_backend'), 10, 1);
			// add_action('woocommerce_receipt_worldline', array($this, 'receipt_page'));
			add_action('init', 'register_session');
			// if (isset($_POST['worldline_cus_cancel'])) {
			// 	wc_add_notice("Payment cancelled", "error");
			// }
			
		}
						
		function init_form_fields(){
			
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'paynimo'),
					'type' => 'checkbox',
					'label' => __('Enable Paynimo Payment Module.', 'paynimo'),
					'default' => 'no'),
				'title' => array(
					'title' => __('<span style="color: #a00;">* </span>Title:', 'Paynimo'),
					'type'=> 'text',
					'id'=>"title",
					'desc_tip'    => true,
					'placeholder' => __( 'Paynimo', 'woocommerce' ),
					'description' => __('Your desire title name .it will show during checkout proccess.', 'paynimo'),
					'default' => __('Paynimo', 'paynimo')),
				'description' => array(
					'title' => __('<span style="color: #a00;">* </span>Description:', 'paynimo'),
					'type' => 'textarea',
					'desc_tip'    => true,
					'placeholder' => __( 'Description', 'woocommerce' ),
					'description' => __('Pay securely through Paynimo.', 'paynimo'),
					'default' => __('Pay securely through Paynimo.', 'paynimo')),
				'paynimo_merchant_code' => array(
                    'title' => __('<span style="color: #a00;">* </span>Merchant Code', 'paynimo'),
                    'type' => 'text',					
					'desc_tip'    => true,
					'placeholder' => __( 'Merchant Code', 'woocommerce' ),
                    'description' => __('Merchant Code')),
				'paynimo_request_type' => array(
					'title'       => __( '<span style="color: #a00;">* </span>Request Type', 'woocommerce' ),
					'type'        => 'select',					
					'css'      => 'min-width:137px;',
					'description' => __( 'Choose request type.', 'woocommerce' ),
					'default'     => 'T',
					'desc_tip'    => true,					
					'options'     => array(
						'T'          => __( 'T', 'woocommerce' ),						
					)
				),
				'paynimo_key' => array(
					'title' => __('<span style="color: #a00;">* </span>Key', 'paynimo'),
					'type' => 'text',					
					'desc_tip'    => true,
					'placeholder' => __( 'Key', 'woocommerce' ),
					'description' => __('Key')),
				'paynimo_iv' => array(
					'title' => __('<span style="color: #a00;">* </span>IV', 'paynimo'),
					'type' => 'text',					
					'desc_tip'    => true,
					'placeholder' => __( 'IV', 'woocommerce' ),
					'description' => __('IV')),
				'paynimo_webservice_locator' => array(
					'title'       => __( '<span style="color: #a00;">* </span>Webservice Locator', 'woocommerce' ),
					'type'        => 'select',					
					'css'      => 'min-width:137px;',
					'description' => __( 'Choose Webservice Locator.', 'woocommerce' ),
					'default'     => 'Test',
					'desc_tip'    => true,
					'options'     => array(
						'Test'          => __( 'TEST', 'woocommerce' ),
						'Live'          => __( 'LIVE', 'woocommerce' ),
					)
				),
				'paynimo_hashalgo' => array(
					'title'       => __( '<span style="color: #a00;">* </span>Hashing Algorithm', 'woocommerce' ),
					'type'        => 'select',					
					'css'      => 'min-width:137px;',
					'description' => __( 'Choose Hashing Algorithm.', 'woocommerce' ),
					'default'     => 'SHA3-512',
					'desc_tip'    => true,
					'options'     => array(
						'SHA3-512'          => __( 'SHA3-512', 'woocommerce' ),
						'SHA3-256'          => __( 'SHA3-256', 'woocommerce' ),
					)
				),							
				'paynimo_merchant_scheme_code' => array(
					'title' => __('<span style="color: #a00;">* </span>Merchant Scheme Code', 'paynimo'),
					'type' => 'text',					
					'desc_tip'    => true,
					'placeholder' => __( 'Merchant Scheme Code', 'woocommerce' ),
					'description' => __('Merchant Scheme Code')),
				'paynimo_success_msg' => array(
					'title' => __('<span style="color: #a00;">* </span>Success Message', 'paynimo'),
					'type' => 'textarea',					
					'desc_tip'    => true,
					'default' => 'Thank you for shopping with us. Your account has been charged and your transaction is successful.',
					'description' => __('Success Message')),
				'paynimo_decline_msg' => array(
					'title' => __('<span style="color: #a00;">* </span>Decline Message', 'paynimo'),
					'type' => 'textarea',					
					'desc_tip'    => true,
					'default' => 'Thank you for shopping with us. However, the transaction has been declined.',
					'description' => __('Decline Message')),
				'paynimo_redirect_msg' => array(
					'title' => __('<span style="color: #a00;">* </span>Redirect Message', 'paynimo'),
					'type' => 'textarea',					
					'desc_tip'    => true,
					'default' => 'Thank you for your order. We are now redirecting you to paynimo to make payment.',
					'description' => __('Redirect Message')),
			
			);
		}
		
		public function admin_options(){			
			echo '<h3>'.__('Paynimo Payment Gateway', 'paynimo').'</h3>';
			
			?>
			<a href="#" target="_blank"><img src="<?php echo $this->icon=plugins_url('images/paynimo.png' , __FILE__ );?>"/></a>			
			<?php						
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}
		
		function payment_fields(){
			if($this->description) echo wpautop(wptexturize($this->description));
		}
		
		function receipt_page($order){			
			echo '<p>'.__('Thank you for your order, please click the button below to pay with Paynimo.', 'paynimo').'</p>';
			echo $this->generate_paynimo_form($order);
		}
		
		function process_payment($order_id){
			
			$order = new WC_Order($order_id);
			return array('result' => 'success', 'redirect' => add_query_arg('order-pay', $order->get_id(), add_query_arg('key', $order->get_order_key(), get_permalink(woocommerce_get_page_id('pay')))));
			// return array('result' => 'success', 'redirect' => add_query_arg('order-pay', $order->get_id(), add_query_arg('key', $order->get_order_key(), get_permalink(wc_get_page_id('pay')))));
		}

		// function process_payment($order_id){
		// 	$order = new WC_Order($order_id);
		// 	$redirect_url = $this->get_return_url($order); // Get the order received URL
		// 	return array('result' => 'success', 'redirect' => $redirect_url);
		// }
		
		
		function check_paynimo_response(){
			if (isset($_GET['wc-api']) && $_GET['wc-api'] == 'woocommerce_paynimo'){
				
				global $woocommerce;
				
	            $msg['class']   = 'error';
	            $msg['message'] = $this->paynimo_decline_msg;
	            $order_id = $_SESSION['order_id'];
   				 $order = new WC_Order($order_id);
	            if($_POST){
	            	$response = $_POST;

	            	if(is_array($response)){
	            		$str = $response['msg'];
	            	}else if(is_string($response) && strstr($response, 'msg=')){
	            		$outputStr = str_replace('msg=', '', $response);
	            		$outputArr = explode('&', $outputStr);
	            		$str = $outputArr[0];
	            	}else {
	            		$str = $response;
	            	}
	            	$transactionResponseBean = new TransactionResponseBean();
	            	
	            	$transactionResponseBean->setResponsePayload($str);
	            	$transactionResponseBean->setKey($this->paynimo_key);
	            	$transactionResponseBean->setIv($this->paynimo_iv);
	            	
	            	$response = $transactionResponseBean->getResponsePayload();
	            	
	            	$response1 = explode('|', $response);
	            	$firstToken = explode('=', $response1[0]);
	            	$status = $firstToken[1];
	            	
	            	$order_id = $_SESSION['order_id']; 	            	
	            	$transauthorised = false;
	            	if($order_id != ''){            		
	            		try {
	            			$order = new WC_Order($order_id);
	            			
	            			if($order->status !=='completed'){            				
	            				if($status == '300') {            					 
	            					$transauthorised = true;
	            					$msg['message'] = $this->paynimo_success_msg;
	            					$msg['class'] = 'success';
	            					
	            					if($order->status != 'processing'){
	            						$order->payment_complete();
	            					}
	            					$woocommerce->cart->empty_cart();
	            					
	            				}else{
	            					$msg['class'] = 'error';
	            					$msg['message'] = $this->paynimo_decline_msg;
	            				}
	            			}
	            		} catch (Exception $e) {            			
	            			$msg['class'] = 'error';
	            			$msg['message'] = $this->paynimo_decline_msg;
	            		}	            		 
	            	}
	            }
	           
	            if ( function_exists( 'wc_add_notice' ) )
	            {
	            	wc_add_notice( $msg['message'], $msg['class'] );
	            
	            }
	            else
	            {
	            	if($msg['class']=='success'){
	            		$woocommerce->add_message( $msg['message']);
	            	}else{
	            		$woocommerce->add_error( $msg['message'] );            
	            	}	            	
	            }
				
	            $return_url = $order->get_checkout_order_received_url();
				$redirect_url = apply_filters( 'woocommerce_get_return_url', $return_url, $order );
				wp_redirect( $redirect_url );
				// wp_redirect( "http://localhost/wordpress642/index.php/checkout/order-received/50/?key=wc_order_wExwzbHZG5los" );
				exit;
			}
		}
		
		public function generate_paynimo_form($order_id){
			global $woocommerce;
			$order = new WC_Order($order_id);
			// print_r($_SESSION);die;
			// print_r($order);			
			$_SESSION['order_id'] = $order_id;
			$order_id = $order_id.'_'.date("ymds");			
			$transactionRequestBean = new TransactionRequestBean(); 
			
			$merchant_txn_id = rand(1,1000000);
			$cur_date = date("d-m-Y");
			$returnUrl = $this->notify_url;
			
			$transactionRequestBean->setMerchantCode($this->paynimo_merchant_code);
			$transactionRequestBean->setRequestType($this->paynimo_request_type);
			$transactionRequestBean->setMerchantTxnRefNumber($merchant_txn_id);
			$transactionRequestBean->setHashAlgo($this->paynimo_hashalgo);
			
			if($this->paynimo_webservice_locator == 'Test'){
				$transactionRequestBean->setAmount('1.00');
		    	$transactionRequestBean->setBankCode('470');
		    	$transactionRequestBean->setWebServiceLocator('https://www.tpsl-india.in/PaymentGateway/TransactionDetailsNew.wsdl');
		    	$transactionRequestBean->setShoppingCartDetails($this->paynimo_merchant_scheme_code.'_1.0_0.0');
				
			}else{
				$transactionRequestBean->setAmount($order->order_total);		    	
		    	$transactionRequestBean->setWebServiceLocator('https://www.tpsl-india.in/PaymentGateway/TransactionDetailsNew.wsdl');
		    	$shoppingCartStr = $this->paynimo_merchant_scheme_code.'_'.$order->order_total.'_0.0';
		    	$transactionRequestBean->setShoppingCartDetails($shoppingCartStr);
			}
			$shipping_first_name = $order->get_shipping_first_name();
			$shipping_last_name = $order->get_shipping_last_name();
			if(isset($shipping_first_name) && isset($shipping_last_name)){
				$transactionRequestBean->setCustomerName($shipping_first_name. ' '.$shipping_last_name);
			}
			$transactionRequestBean->setReturnURL($returnUrl);
			
			$transactionRequestBean->setTxnDate($cur_date);
			$transactionRequestBean->setKey($this->paynimo_key);
			$transactionRequestBean->setIv($this->paynimo_iv);
			if ( is_user_logged_in() )
				$customer_id=  get_current_user_id();
			else
				$customer_id = md5(wp_generate_password( 32 ));
			
			$transactionRequestBean->setUniqueCustomerId($customer_id);
			$transactionRequestBean->setITC('email:'.$order->get_billing_email());
			$transactionRequestBean->setEmail($order->get_billing_email());
			$transactionRequestBean->setMobileNumber($order->get_billing_phone());
			$customerName = $order->get_billing_first_name(). " ". $order->get_billing_last_name();
			$transactionRequestBean->setCustomerName($customerName);
			$url = $transactionRequestBean->getTransactionToken();
			
			$form = '<form action="' . esc_url( $url ) . '" method="post" id="paynimo_payment_form" target="_top">
			
			<!-- Button Fallback -->
			<div class="payment_buttons">
			<input type="submit" class="button alt" id="submit_paynimo_payment_form" value="' . __( 'Pay via Paynimo', 'woocommerce' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
			</div>
			<script type="text/javascript">
			jQuery(".payment_buttons").hide();
			jQuery(function(){
				jQuery("body").block({
					message: "' . esc_js( __( $this->paynimo_redirect_msg , 'woocommerce' ) ) . '",					
					overlayCSS:
					{
					background: "#fff",
					opacity: 0.6
				},
					css: {
					padding:        "20px",					
					textAlign:      "center",
					color:          "#555",
					border:         "3px solid #aaa",
					backgroundColor:"#fff",
					cursor:         "wait",
					lineHeight:     "32px",
				}
				});
				jQuery("#submit_paynimo_payment_form").click();
			});
			</script>
			</form>';
			return $form;
			
		}
		
		function get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
					$has_parent = $page->post_parent;
					while($has_parent) {
						$prefix .=  ' - ';
						$next_page = get_page($has_parent);
						$has_parent = $next_page->post_parent;
					}
				}
				// add to page list array array
				$page_list[$page->ID] = $prefix . $page->post_title;
			}
			return $page_list;
		}
	}
	/**
	 * Add the Gateway to WooCommerce
	 **/
	function woocommerce_add_paynimo_gateway($methods) {
		$methods[] = 'woocommerce_paynimo';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_paynimo_gateway' );
}

?>