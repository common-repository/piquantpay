<?php
/**
 * Plugin Name: Piquant Pay
 * Plugin URI: http://piquantpay.com
 * Description: Picquant Pay is a cost effective payment gateway allowing merchants to process payments online for all major Credit and Debit Card schemes including Visa, MasterCard and American Express. Piquant Pay is quick and easy to integrate in all major WooCommerce sites and is dedicated to making payments better. There is no need to spend weeks on paperwork or security compliance procedures, no more lost conversions as you don’t support a shopper’s preferred payment type or as a result of security fears as Piquant Pay offers Mastercard 3 D Secure, Verify by Visa and Amex Secure Code for payments and settlement in Sterling. Piquant Pay makes payments intuitive and safe for merchants and their customers.
 * Version: 1.0.6
 * Author: David * 
 * Contributors: David
 * Requires at least: 4.0
 * Tested up to: 4.7
 *
 * Text Domain: woo-axept-server
 * * Domain Path: /lang/
 *
 * @package Axept Server Gateway for WooCommerce
 * @author David
 */

add_action('plugins_loaded', 'init_woocommerce_axeptserver', 0);
function init_woocommerce_axeptserver() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
		load_plugin_textdomain('woo-axept-server', false, dirname( plugin_basename( __FILE__ ) ) . '/lang');
	class woocommerce_axeptserver extends WC_Payment_Gateway{

		public function __construct() {
			global $woocommerce;

			$this->id            = 'axeptserver';
			$this->method_title  = __('Axept Server', 'woo-axept-server');
			$this->icon     		 = apply_filters( 'woocommerce_axeptserver_icon', '' );
			$this->has_fields    = false;
			$this->notify_url    = add_query_arg( 'wc-api', 'woocommerce_axeptserver', home_url( '/' ) );

			$default_card_type_options = array(
				'VISA' => 'VISA',
				'MC' => 'MasterCard',
				'AMEX' => 'American Express',
				'DISC' => 'Discover',
				'DC' => 'Diner\'s Club',
				'JCB' => 'JCB Card'
			);

			$this->card_type_options = apply_filters( 'woocommerce_axeptserver_card_types', $default_card_type_options );
			
			// load form fields
			$this->init_form_fields();

			// initialise settings
			$this->init_settings();

      // variables
      $this->title                        = $this->settings['title'];
      $this->description                  = $this->settings['description'];                    
      $this->mode                         = $this->settings['mode'];
      
      //Gateway Setting code                    
      $this->MerchantStoreId              = $this->settings['MerchantStoreId'];
      $this->MerchantDepartmentID         = $this->settings['MerchantDepartmentID'];
      $this->MerchantSignatureKeyId       = $this->settings['MerchantSignatureKeyId'];
      $this->MerchantName                 = $this->settings['MerchantName'];
      $this->VisaPaMerchantNumber         = $this->settings['VisaPaMerchantNumber'];
      $this->MasterCardPaMerchantNumber   = $this->settings['MasterCardPaMerchantNumber'];
      $this->CountrySelectId              = $this->settings['CountrySelectId'];
      $this->CurrencySelectId             = $this->settings['CurrencySelectId'];
      $this->CscMatrix             = $this->settings['CscMatrix'];
      $this->AvsHouseMatrix             = $this->settings['AvsHouseMatrix'];
      $this->AvsPostCodeMatrix             = $this->settings['AvsPostCodeMatrix'];
      $this->PaMatrix             = $this->settings['PaMatrix'];
      $this->secret_key                   = $this->settings['secret_key'];
      

      if( $this->mode == 'test' ){
        $this->gateway_url  = 'https://ppe.optpg.com/checkoutreview';
      }else if( $this->mode == 'live' ){
        $this->gateway_url  = 'https://optpg.com/CheckoutV2';
      }

      // actions
      add_action( 'init', array( $this, 'successful_request') );
      add_action( 'woocommerce_api_woocommerce_axeptserver', array( $this, 'successful_request' ) );
      add_action( 'woocommerce_receipt_axeptserver', array( $this, 'receipt_page' ) );
      add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

      if ( !$this->is_valid_for_use() ) $this->enabled = false;
		}

		/**
		 * get_icon function.
		 *
		 * @access public
		 * @return string
		 */
		function get_icon() {
			global $woocommerce;

			$icon = '';
			if ( $this->icon ) {
				// default behavior
				$icon = '<img src="' . $this->force_ssl( $this->icon ) . '" alt="' . $this->title . '" />';
			} 

			return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
		}

    /**
     * Check if this gateway is enabled and available in the user's country
     */
    function is_valid_for_use() {

        return true;
    }

    /**
     * Admin Panel Options
     **/
    public function admin_options(){
      ?>
      <h3><?php _e('Axept Server', 'woo-axept-server'); ?></h3>
      <p><?php _e('Payment via Axept, once clicked you will be redirected to the Axept payment gateway screen.', 'woo-axept-server'); ?></p>
      <table class="form-table">
        <?php
        if ( $this->is_valid_for_use() ){
          // Generate the HTML For the settings form.
          $this->generate_settings_html();
        }else{
          ?>
          <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woothemes' ); ?></strong>: <?php _e( 'Axept Server does not support your store currency.', 'woothemes' ); ?></p></div>
          <?php
        }
        ?>
      </table><!--/.form-table-->
      <?php
		}

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

      //  array to generate admin form
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable/Disable', 'woo-axept-server' ),
          'type' => 'checkbox',
          'label' => __( 'Enable Axept Server', 'woo-axept-server' ),
          'default' => 'yes'
        ),
          'title' => array(
          'title' => __( 'Title', 'woo-axept-server' ),
          'type' => 'text',
          'description' => __( 'This is the title displayed to the user during checkout.', 'woo-axept-server' ),
          'default' => __( 'Axept Server', 'woo-axept-server' )
        ),
        'description' => array(
          'title' => __( 'Description', 'woo-axept-server' ),
          'type' => 'textarea',
          'description' => __( 'This is the description which the user sees during checkout.', 'woo-axept-server' ),
          'default' => __("Payment via Axept, once clicked you will be redirected to the Axept payment gateway screen.", 'woo-axept-server')
        ),
        'mode' => array(
          'title' => __('Mode Type', 'woo-axept-server'),
          'type' => 'select',
          'options' => array(
            'test' => 'Test',
            'live' => 'Live'
          ),
          'default' => 'test',
          'description' => __( 'Select Test or Live modes.', 'woo-axept-server' )
        ),
        'secret_key' => array(
          'title' => __( 'Secret Key', 'woo-axept-server' ),
          'type' => 'text',
          'label' => __( 'Enter Secret Key', 'woo-axept-server' ),
          'default' => '',
          'description' => __( 'Please enter your Secret Key provided by Axept.', 'woo-axept-server' )
        ),  
        'MerchantStoreId' => array(
          'title' => __( 'MerchantStoreId', 'woo-axept-server' ),
          'type' => 'text',
          'label' => __( 'Enter MerchantStoreId', 'woo-axept-server' ),
          'default' => '',
          'description' => __( 'Please enter your MerchantStoreId provided by Axept.', 'woo-axept-server' )
        ),
        'MerchantDepartmentID' => array(
          'title' => __( 'MerchantDepartmentID', 'woo-axept-server' ),
          'type' => 'text',
          'label' => __( 'Enter MerchantDepartmentID', 'woo-axept-server' ),
          'default' => '',
          'description' => __( 'Please enter your MerchantDepartmentID provided by Axept.', 'woo-axept-server' )
        ),  
        'MerchantSignatureKeyId' => array(
          'title' => __( 'MerchantSignatureKeyId', 'woo-axept-server' ),
          'type' => 'text',
          'label' => __( 'Enter MerchantSignatureKeyId', 'woo-axept-server' ),
          'default' => '',
          'description' => __( 'Please enter your MerchantSignatureKeyId provided by Axept.', 'woo-axept-server' )
        ),
        'MerchantName' => array(
            'title' => __( 'MerchantName', 'woo-axept-server' ),
            'type' => 'text',
            'label' => __( 'Enter MerchantName', 'woo-axept-server' ),
            'default' => '',
            'description' => __( 'Please enter your MerchantName provided by Axept.', 'woo-axept-server' )
        ),
        'VisaPaMerchantNumber' => array(
            'title' => __( 'VisaPaMerchantNumber', 'woo-axept-server' ),
            'type' => 'text',
            'label' => __( 'Enter VisaPaMerchantNumber', 'woo-axept-server' ),
            'default' => '',
            'description' => __( 'Please enter your VisaPaMerchantNumber provided by Axept.', 'woo-axept-server' )
        ),
        'MasterCardPaMerchantNumber' => array(
            'title' => __( 'MasterCardPaMerchantNumber', 'woo-axept-server' ),
            'type' => 'text',
            'label' => __( 'Enter MasterCardPaMerchantNumber', 'woo-axept-server' ),
            'default' => '',
            'description' => __( 'Please enter your MasterCardPaMerchantNumber provided by Axept.', 'woo-axept-server' )
        ),
        'CountrySelectId' => array(
            'title' => __( 'Country', 'woo-axept-server' ),
            'type' => 'select',
            'options' => getCountryCodes(),
            'default' => '826',    
            'description' => __( 'Please select Country.', 'woo-axept-server' )
        ), 
	'CurrencySelectId' => array(
		'title' => __( 'Currency', 'woo-axept-server' ),
		'type' => 'select',
		'options' => getCurrencyCodes(),
		'default' => '826',    
		'description' => __( 'Please select Currency.', 'woo-axept-server' )
	), 
	'CscMatrix' => array(
		'title' => __( 'CscMatrix', 'woo-axept-server' ),
		'type' => 'select',
		'options' => getCscMatrixOptions(),
		'default' => '0',    
		'description' => __( 'Please select If the merchant wishes to validate the House Post Code.', 'woo-axept-server' )
	),    
	'AvsHouseMatrix' => array(
			'title' => __( 'AvsHouseMatrix', 'woo-axept-server' ),
			'type' => 'select',
			'options' => getAvsHouseMatrixOptions(),
			'default' => '0',    
			'description' => __( 'Please select If the merchant wishes to validate the Avs House Matrix.', 'woo-axept-server' )
	),   
	'AvsPostCodeMatrix' => array(
			'title' => __( 'AvsPostCodeMatrix', 'woo-axept-server' ),
			'type' => 'select',
			'options' => getAvsPostCodeMatrixOptions(),
			'default' => '0',    
			'description' => __( 'Please select If the merchant wishes to validate the Avs Post Code Matrix.', 'woo-axept-server' )
	),      
	'PaMatrix' => array(
			'title' => __( 'PaMatrix', 'woo-axept-server' ),
			'type' => 'select',
			'options' => getPaMatrixOptions(),
			'default' => '0',    
			'description' => __( 'Please selectIf the merchant wishes to validate the results of the Payer Authentication check as part of the transaction.', 'woo-axept-server' )
	)        
      );
      
      
    }

		/**
		 * Generate the axeptserver button link
		 **/
    public function generate_axeptserver_form( $order_id ) {
            global $woocommerce;                        

            $order = new WC_Order( $order_id );
            
            $axept_val = $this->getAxeptGatewayValue($order_id);
                
            $OptoReq 		= $axept_val['OptoReq'];
            $OptoHmac 		= $axept_val['OptoHmac'];
            
           

            wc_enqueue_js('
                            jQuery("body").block({
                                            message: "<img src=\"'.esc_url( $woocommerce->plugin_url() ).'/assets/images/select2-spinner.gif\" alt=\"Redirecting...\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to verify your card.', 'woothemes').'",
                                            overlayCSS:
                                            {
                                                    background: "#fff",
                                                    opacity: 0.6
                                            },
                                            css: {
                                            padding:        20,
                                            textAlign:      "center",
                                            color:          "#555",
                                            border:         "3px solid #aaa",
                                            backgroundColor:"#fff",
                                            cursor:         "wait",
                                            lineHeight:		"32px"
                                        }
                                    });
                            jQuery("#submit_axeptserver_payment_form").click();
                    ');

                return '<form action="'.esc_url( $this->gateway_url ).'" method="post" id="axeptserver_payment_form">              <input type="hidden" name="OptoReq" value="'.$OptoReq.'">
                    <input type="hidden" name="OptoHmac" value="'.$OptoHmac.'">                    
                    <input type="submit" class="button alt" id="submit_axeptserver_payment_form" value="'.__('Submit', 'woothemes').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woothemes').'</a>
                        </form>';

		}

		/**
		 *
	   * process payment
	   *
	   */
	  function process_payment( $order_id ) {
                global $woocommerce;

                $order = new WC_Order( $order_id );

                $time_stamp = date("ymdHis");
                $orderid = $time_stamp . "-" . $order_id;

                
                $sd_arg['Amount']              = $order->order_total;
                $sd_arg['CustomerEMail'] 		   = $order->billing_email;
                $sd_arg['BillingSurname'] 		 = $order->billing_last_name;
                $sd_arg['BillingFirstnames']   = $order->billing_first_name;
                $sd_arg['BillingAddress1'] 		 = $order->billing_address_1;
                $sd_arg['BillingAddress2'] 		 = $order->billing_address_2;
                $sd_arg['BillingCity'] 			   = $order->billing_city;
                
                if( $order->billing_country == 'US' ){
                    $sd_arg['BillingState'] 		= $order->billing_state;
                }else{
                    $sd_arg['BillingState'] 		= '';
                }
                
                $sd_arg['BillingPostCode'] 		= $order->billing_postcode;
                $sd_arg['BillingCountry'] 		= $order->billing_country;
                $sd_arg['BillingPhone'] 		  = $order->billing_phone;

                $sd_arg['DeliverySurname'] 		= $order->shipping_last_name;
                $sd_arg['DeliveryFirstnames'] = $order->shipping_first_name;
                $sd_arg['DeliveryAddress1']   = $order->shipping_address_1;
                $sd_arg['DeliveryAddress2']   = $order->shipping_address_2;
                $sd_arg['DeliveryCity'] 		= $order->shipping_city;

                if( $order->shipping_country == 'US' ){
                  $sd_arg['DeliveryState'] 		= $order->shipping_state;
                }else{
                  $sd_arg['DeliveryState'] 		= '';
                }

                $sd_arg['DeliveryPostCode'] 	= $order->shipping_postcode;
                $sd_arg['DeliveryCountry'] 		= $order->shipping_country;

                $sd_arg['Description'] 			  = sprintf(__('Order #%s' , 'woothemes'), $order->id);
                $sd_arg['Currency'] 			    = get_woocommerce_currency();
                $sd_arg['VPSProtocol'] 			  = 3.00;
                
                
                $sd_arg['VendorTxCode'] 		  = $orderid;
                
                $sd_arg['NotificationURL'] 		= $this->notify_url;
                
                
                
                //david  code for axept payment gateway                
                $axept_val = $this->getAxeptGatewayValue($order_id);                
                
                
                $sd_arg['OptoReq'] 		       = $axept_val['OptoReq'];
                $sd_arg['OptoHmac'] 		     = $axept_val['OptoHmac'];

                $post_values = "";
                foreach( $sd_arg as $key => $value ) {
                  $post_values .= "$key=" . urlencode( $value ) . "&";
                }
                $post_values = rtrim( $post_values, "& " );

                $response = wp_remote_post(
                  $this->gateway_url,
                  array(
                    'body' => $post_values,
                    'method' => 'POST',
                    'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                    'sslverify' => FALSE
                  )
                );
                

            if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
                $resp = json_decode($response['body']);                
                
                if ($resp->Status != 'INVALID'){
                        //$order->add_order_note( 'test notes' );
                        set_transient( 'axept_server_next_url', $this->gateway_url );

                        $redirect = $order->get_checkout_payment_url( true );

                        return array(
                                'result' 	=> 'success',
                                'redirect'	=> $redirect
                        );

                }else{                    
                    //echo $resp->StatusDetail;
                    //exit;                   
                    
                        if(isset($resp->StatusDetail)){
                            wc_add_notice( sprintf('Transaction Failed. %s - %s', $resp->Status, $resp->StatusDetail ), 'error' );
                        }else{
                            wc_add_notice( sprintf( 'Transaction Failed with %s - unknown error.', $resp->Status ), 'error' );
                        }
                    }

              }else{

                        wc_add_notice( __('Gateway Error. Please Notify the Store Owner about this error.', 'woo-axept-server'), 'error' );
          }
		}

		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {
			global $woocommerce;

			echo '<p>'.__('Thank you for your order.', 'woo-axept-server').'</p>';

			echo $this->generate_axeptserver_form( $order );

		}


		/**
		 * Successful Payment!
		 **/
		function successful_request() {
			global $woocommerce;
                        
      $params = array();
      $params['Status'] = 'INVALID';
      $order = "";
      $redirect_url = "";      
      
      if(!empty(sanitize_text_field($_REQUEST['OptoRes']))){

          $optoRes = sanitize_text_field($_REQUEST['OptoRes']);
          $signature = sanitize_text_field($_REQUEST['signature']);
          $optomanyPublicKey = '-----BEGIN PUBLIC KEY-----
          MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkb+c5fr6+3K4RTryiLLh
          G/D6torUGRVZPdS/wLrK+N1BuU+B9QFovVMSMTtuGatmT80Kl9m/hqQaWMfGHkjU
          5IWUgXGT7xCkyRyikICX+BJ8qQ2NSWJVYm4a/jFQ+D5Sge3ckWbWOKCD7Q9vXuMy
          fgZPlxYlaB9QRvHv5dgkjK46+YNtFUN0mICTyz96IQ4QiNKC/MYSt8ZnxYrqgEV+
          xLJulo5VcMqcsGubbbUMuHP6Vw/597C5appUGsgdTj13/dUBy0/SlXKFx7SA/Mti
          RSGxWmpcZFisRyGO7i+jP9fd0RYNSbEkFxWlW2gFbfqI4Gf1lHPZ1tZ6uefOHki9
          vwIDAQAB
          -----END PUBLIC KEY-----';
          // undo signature url encoding
          $signature = base64urldecode($signature);

          // base64decode signature
          $signature = base64_decode($signature);
          // base64decode optores
          $optoRes = base64_decode($optoRes);
          // convert optores to ascii
          //$optoRes = iconv('UTF-8', 'ASCII', $optoRes);


          $arr_optoRes = explode("|",$optoRes);

          /*print"<pre>";
          print_r($arr_optoRes);
          exit;*/

          $filed_sep	= "";

          $error_code = $arr_optoRes[0];
          $arr_error_code = explode($filed_sep,$error_code);
          
          $reference = $arr_optoRes[4];
          $arr_reference_code = explode($filed_sep,$reference);
          
          $order_id = 0;                            
          if($arr_reference_code[0] =='Reference'){
              $order_id = $arr_reference_code[1];
          }
          
          $EftPaymentId = $arr_optoRes[11];
          $arr_EftPaymentId_code = explode($filed_sep,$EftPaymentId);
          
          $trasns_id = 0;                            
          if($arr_EftPaymentId_code[0] =='EftPaymentId'){
              $trans_id = $arr_EftPaymentId_code[1];
          }
          
          //print_r($arr_error_code);              
          //echo $order_id;
          
          if($order_id > 0){
              $order = new WC_Order( $order_id );
          }
          
          
          $status_trans = $arr_optoRes[2];
          $status_trans_code = explode($filed_sep,$status_trans);

          $status_trans = 0;                            
          if($status_trans_code[0] =='Status'){
              $status_trans = $status_trans_code[1];
          }
          
          //mail('david@123789.org',"Payment Values",print_r($_REQUEST,1));
          //mail('david@123789.org',"Payment Order Values",$order_id);
          
          if($arr_error_code[0] == 'ErrorCode' && $arr_error_code[1] == 0 && $order_id > 0 && strtolower($status_trans) != 'rejected' && strtolower($status_trans) != 'declined' && strtolower($status_trans) != 'cancelled' ){
              $params = array('Status' => 'OK', 'StatusDetail' => __('Transaction acknowledged.', 'woo-axept-server') );
              $redirect_url = $this->get_return_url( $order );
              
              update_post_meta( $order_id, 'transactionid', $trans_id );
                                              
              $order->add_order_note( __('Axept Direct payment completed', 'woo-axept-server') . ' ( ' . __('Transaction ID: ','woo-axept-server') . $trans_id . ' )' );
              
              $order->update_status('processing', __('Awaiting admin approval', 'woo-axept-server'));
              
              
              $order->payment_complete();
              //$woocommerce->cart->empty_cart();
          }
          else{
              
              $error_msg_arr  = esc_html($arr_optoRes[1]);
              $error_msg_text = esc_html(explode($filed_sep,$error_msg_arr));
              $msg_str        = esc_html($error_msg_text[1]);
              
              $params         = array('Status' => 'INVALID', 'StatusCancel' => $status_trans, 'StatusDetail' => __('Transaction errored - ', 'woo-axept-server') . $msg_str );
              
              /*print"<pre>";
              print_r($params);
              exit;*/                                
              
              $redirect_url = $order->get_cancel_order_url();                                
          }

      }
      else{
          $params = array('Status' => 'INVALID', 'StatusCode' => 302, 'StatusDetail' => __('Unknown error.', 'woo-axept-server')  );
          //$redirect_url = $order->get_cancel_order_url();
          $redirect_url = home_url();
          
      }
      
      $params['RedirectURL'] =  $this->force_ssl( $redirect_url );      
      $sub = "sub".time();

      //change the email address to debug
      //mail("david@123789.org",$sub,print_r($_REQUEST,1));      
      //mail("david@123789.org","Test messaaaa",print_r($arr_optoRes,1));     
      
      if($params['Status'] == 'OK' || $params['StatusCode'] == 302 ){
          wp_redirect( $this->force_ssl( $redirect_url ) );
          exit;
      }
      elseif($params['StatusCancel'] == 'Cancelled' || $params['StatusCancel'] == 'Rejected' || $params['StatusCancel'] == 'Declined' ){
          $checkout_url = $woocommerce->cart->get_checkout_url();
          
          $checkout_url_new = add_query_arg( 'gateway_status', strtolower($params['StatusCancel']), $checkout_url );                          
          wp_redirect( $this->force_ssl( $checkout_url_new ) );
          exit;
      }
      else{
          echo json_encode($params);
          exit();
      }
		}

		

		private function force_ssl($url){
			if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$url = str_replace( 'http:', 'https:', $url );
			}
			return $url;
		}
                
                
    public function getAxeptGatewayValue($order_id){
            global $woocommerce;

            $order = new WC_Order( $order_id );

            //david  code for axept payment gateway
            $filed_sep	= "FS";
            $filed_sep	= "";
            $ref_num 	  = $order_id;
            $return_url = $this->notify_url;
            $amount     = $order->order_total;
            //$amount  = 0.1;
            
            
            //fetch the setting here
            $MerchantStoreId = $this->MerchantStoreId;
            $MerchantDepartmentID = $this->MerchantDepartmentID;
            $MerchantSignatureKeyId = $this->MerchantSignatureKeyId;
            $MerchantName = $this->MerchantName;
            $VisaPaMerchantNumber = $this->VisaPaMerchantNumber;
            $MasterCardPaMerchantNumber = $this->MasterCardPaMerchantNumber;
            $CountrySelectId = $this->CountrySelectId;
            $CurrencySelectId = $this->CurrencySelectId;
            $CscMatrix = $this->CscMatrix;
            $AvsHouseMatrix = $this->AvsHouseMatrix;
            $AvsPostCodeMatrix = $this->AvsPostCodeMatrix;
            $PaMatrix = $this->PaMatrix;
            
            $address1 = $order->billing_address_1;
            $postcode = $order->billing_postcode;
            

           $optoreq_first = 'Reference'.$filed_sep.$ref_num.'|ReturnUrl'.$filed_sep.$return_url.'|MerchantStoreId'.$filed_sep.$MerchantStoreId.'|MerchantDepartmentID'.$filed_sep.$MerchantDepartmentID.'|MerchantSignatureKeyId'.$filed_sep.$MerchantSignatureKeyId.'|CountryId'.$filed_sep.$CountrySelectId.'|CardCollectionId'.$filed_sep.'1|AuthTypeId'.$filed_sep.'0|ChemId'.$filed_sep.'4|Amount'.$filed_sep.$amount.'|Description'.$filed_sep.'Antem|Currency'.$filed_sep.$CurrencySelectId.'|CardHolderKnown'.$filed_sep.'True|PayerAuth'.$filed_sep.'True|AvsHouseMatrix'.$filed_sep.$AvsHouseMatrix.'|AvsPostCodeMatrix'.$filed_sep.$AvsPostCodeMatrix.'|CscMatrix'.$filed_sep.$CscMatrix.'|Address1'.$filed_sep.$address1.'|PostCode'.$filed_sep.$postcode.'|MerchantName'.$filed_sep.$MerchantName.'|MerchantUrl'.$filed_sep.'http://www.optomany.com|VisaPaMerchantNumber'.$filed_sep.$VisaPaMerchantNumber.'|MasterCardPaMerchantNumber'.$filed_sep.$MasterCardPaMerchantNumber.'|Paypal'.$filed_sep.'false|Donation'.$filed_sep.'false|PaMatrix'.$filed_sep.$PaMatrix;
            

            $optoreq_base64_encode	= base64_encode($optoreq_first);            
            $secret_key         =       $this->secret_key;

            $OptoHmac 				= hash_hmac('sha256', $optoreq_first, $secret_key);

            $OptoHmac_ascii = hexstr($OptoHmac);

            $OptoHmac_base64_encode	= base64_encode($OptoHmac_ascii);


            $axept_val = array();
            $axept_val['OptoReq'] 		= $optoreq_base64_encode;
            $axept_val['OptoHmac'] 		= $OptoHmac_base64_encode;

            return $axept_val;
            exit;
}   
	}

	/**
	 * Add the gateway to WooCommerce
	 **/
	function add_axeptserver_gateway( $methods ){
            $methods[] = 'woocommerce_axeptserver';
            return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'add_axeptserver_gateway' );
}

function base64urldecode($data) {
    $search = array('%2B','%2F','%3D');
    $replace = array('+','/','=');
    return str_ireplace($search, $replace, $data);
}

function hexstr($hexstr) {
  $hexstr = str_replace(' ', '', $hexstr);
  $hexstr = str_replace('\x', '', $hexstr);
  $retstr = pack('H*', $hexstr);
  return $retstr;
}

function strhex($string) {
  $hexstr = unpack('H*', $string);
  return array_shift($hexstr);
}

function getCountryCodes(){

    $country_codes = array();    
    $country_codes["004"]   =  __("Afghanistan", "woo-axept-server");
    $country_codes["008"]   =  __("Albania", "woo-axept-server");
    $country_codes["010"]   =  __("Antarctica", "woo-axept-server");
    $country_codes["012"]   =  __("Algeria", "woo-axept-server");
    $country_codes["016"]   =  __("American Samoa", "woo-axept-server");    
    $country_codes["020"]   =  __("Andorra", "woo-axept-server");
    $country_codes["024"]   =  __("Angola", "woo-axept-server");
    $country_codes["028"]   =  __("Antigua and Barbuda", "woo-axept-server");
    $country_codes["031"]   =  __("Azerbaijan", "woo-axept-server");
    $country_codes["032"]   =  __("Argentina", "woo-axept-server");
    $country_codes["036"]   =  __("Australia", "woo-axept-server");
    $country_codes["040"]   =  __("Austria", "woo-axept-server");    
    $country_codes["044"]   =  __("Bahamas, The", "woo-axept-server");
    $country_codes["048"]   =  __("Bahrain", "woo-axept-server");
    $country_codes["050"]   =  __("Bangladesh", "woo-axept-server");
    $country_codes["051"]   =  __("Armenia", "woo-axept-server");
    $country_codes["052"]   =  __("Barbados", "woo-axept-server");
    $country_codes["056"]   =  __("Belgium", "woo-axept-server");
    $country_codes["060"]   =  __("Bermuda", "woo-axept-server");    
    $country_codes["064"]   =  __("Bhutan", "woo-axept-server");
    $country_codes["068"]   =  __("Bolivia", "woo-axept-server");
    $country_codes["070"]   =  __("Bosnia and Herzegovina", "woo-axept-server");
    $country_codes["072"]   =  __("Botswana", "woo-axept-server");
    $country_codes["074"]   =  __("Bouvet Island", "woo-axept-server");
    $country_codes["076"]   =  __("Brazil", "woo-axept-server");
    $country_codes["084"]   =  __("Belize", "woo-axept-server");    
    $country_codes["086"]   =  __("British Indian Ocean Territory", "woo-axept-server");
    $country_codes["090"]   =  __("Solomon Islands", "woo-axept-server");
    $country_codes["092"]   =  __("British Virgin Islands", "woo-axept-server");
    $country_codes["096"]   =  __("Brunei", "woo-axept-server");
    $country_codes["100"]   =  __("Bulgaria", "woo-axept-server");
    $country_codes["104"]   =  __("Burma", "woo-axept-server");
    $country_codes["108"]   =  __("Burundi", "woo-axept-server");    
    $country_codes["112"]   =  __("Belarus", "woo-axept-server");
    $country_codes["116"]   =  __("Cambodia", "woo-axept-server");
    $country_codes["120"]   =  __("Cameroon", "woo-axept-server");
    $country_codes["124"]   =  __("Canada", "woo-axept-server");
    $country_codes["132"]   =  __("Cape Verde", "woo-axept-server");
    $country_codes["136"]   =  __("Cayman Islands", "woo-axept-server");
    $country_codes["140"]   =  __("Central African Republic", "woo-axept-server");    
    $country_codes["144"]   =  __("Sri Lanka", "woo-axept-server");
    $country_codes["148"]   =  __("Chad", "woo-axept-server");
    $country_codes["152"]   =  __("Chile", "woo-axept-server");
    $country_codes["156"]   =  __("China", "woo-axept-server");
    $country_codes["158"]   =  __("Taiwan", "woo-axept-server");
    $country_codes["162"]   =  __("Christmas Island", "woo-axept-server");
    $country_codes["166"]   =  __("Cocos (Keeling) Islands", "woo-axept-server");    
    $country_codes["170"]   =  __("Colombia", "woo-axept-server");
    $country_codes["174"]   =  __("Comoros", "woo-axept-server");
    $country_codes["175"]   =  __("Mayotte", "woo-axept-server");
    $country_codes["178"]   =  __("Congo, Republic of the", "woo-axept-server");
    $country_codes["180"]   =  __("Congo, Democratic Republic of the", "woo-axept-server");    
    $country_codes["184"]   =  __("Cook Islands", "woo-axept-server");
    $country_codes["188"]   =  __("Costa Rica", "woo-axept-server");
    $country_codes["191"]   =  __("Croatia", "woo-axept-server");
    $country_codes["192"]   =  __("Cuba", "woo-axept-server");
    $country_codes["196"]   =  __("Cyprus", "woo-axept-server");
    $country_codes["203"]   =  __("Czech Republic", "woo-axept-server");
    $country_codes["204"]   =  __("Benin", "woo-axept-server");    
    $country_codes["208"]   =  __("Denmark", "woo-axept-server");
    $country_codes["212"]   =  __("Dominica", "woo-axept-server");
    $country_codes["214"]   =  __("Dominican Republic", "woo-axept-server");
    $country_codes["218"]   =  __("Ecuador", "woo-axept-server");
    $country_codes["222"]   =  __("El Salvador", "woo-axept-server");
    $country_codes["226"]   =  __("Equatorial Guinea", "woo-axept-server");
    $country_codes["231"]   =  __("Ethiopia", "woo-axept-server");    
    $country_codes["170"]   =  __("Colombia", "woo-axept-server");
    $country_codes["174"]   =  __("Comoros", "woo-axept-server");
    $country_codes["232"]   =  __("Eritrea", "woo-axept-server");
    $country_codes["233"]   =  __("Estonia", "woo-axept-server");
    $country_codes["234"]   =  __("Faroe Islands", "woo-axept-server");    
    $country_codes["238"]   =  __("Falkland Islands (Islas Malvinas)", "woo-axept-server");
    $country_codes["239"]   =  __("South Georgia and the Islands", "woo-axept-server");
    $country_codes["242"]   =  __("Fiji", "woo-axept-server");
    $country_codes["246"]   =  __("Finland", "woo-axept-server");
    $country_codes["249"]   =  __("France, Metropolitan", "woo-axept-server");
    $country_codes["250"]   =  __("France", "woo-axept-server");
    $country_codes["254"]   =  __("French Guiana", "woo-axept-server");    
    $country_codes["258"]   =  __("French Polynesia", "woo-axept-server");
    $country_codes["260"]   =  __("French Southern and Antarctic Lands", "woo-axept-server");
    $country_codes["262"]   =  __("Djibouti", "woo-axept-server");
    $country_codes["266"]   =  __("Gabon", "woo-axept-server");
    $country_codes["268"]   =  __("Georgia", "woo-axept-server");
    $country_codes["270"]   =  __("Gambia, The", "woo-axept-server");
    $country_codes["275"]   =  __("West Bank", "woo-axept-server");    
    $country_codes["276"]   =  __("Germany", "woo-axept-server");
    $country_codes["288"]   =  __("Ghana", "woo-axept-server");
    $country_codes["292"]   =  __("Gibraltar", "woo-axept-server");
    $country_codes["296"]   =  __("Kiribati", "woo-axept-server");
    $country_codes["300"]   =  __("Greece", "woo-axept-server");    
    $country_codes["304"]   =  __("Greenland", "woo-axept-server");
    $country_codes["308"]   =  __("Grenada", "woo-axept-server");
    $country_codes["312"]   =  __("Guadeloupe", "woo-axept-server");
    $country_codes["316"]   =  __("Guam", "woo-axept-server");
    $country_codes["320"]   =  __("Guatemala", "woo-axept-server");
    $country_codes["324"]   =  __("Guinea", "woo-axept-server");
    $country_codes["328"]   =  __("Guyana", "woo-axept-server");    
    $country_codes["332"]   =  __("Haiti", "woo-axept-server");
    $country_codes["334"]   =  __("Heard Island and McDonald Islands", "woo-axept-server");
    $country_codes["336"]   =  __("Holy See (Vatican City)", "woo-axept-server");
    $country_codes["340"]   =  __("Honduras", "woo-axept-server");
    $country_codes["344"]   =  __("Hong Kong", "woo-axept-server");
    $country_codes["348"]   =  __("Hungary", "woo-axept-server");
    $country_codes["352"]   =  __("Iceland", "woo-axept-server");    
    $country_codes["356"]   =  __("India", "woo-axept-server");
    $country_codes["360"]   =  __("Indonesia", "woo-axept-server");
    $country_codes["364"]   =  __("Iran", "woo-axept-server");
    $country_codes["368"]   =  __("Iraq", "woo-axept-server");
    $country_codes["372"]   =  __("Ireland", "woo-axept-server");    
    $country_codes["376"]   =  __("Israel", "woo-axept-server");
    $country_codes["380"]   =  __("Italy", "woo-axept-server");
    $country_codes["384"]   =  __("Cote d'Ivoire", "woo-axept-server");
    $country_codes["388"]   =  __("Jamaica", "woo-axept-server");
    $country_codes["392"]   =  __("Japan", "woo-axept-server");
    $country_codes["398"]   =  __("Kazakhstan", "woo-axept-server");
    $country_codes["400"]   =  __("Jordan", "woo-axept-server");    
    $country_codes["404"]   =  __("Kenya", "woo-axept-server");
    $country_codes["408"]   =  __("Korea, North", "woo-axept-server");
    $country_codes["410"]   =  __("Korea, South", "woo-axept-server");
    $country_codes["414"]   =  __("Kuwait", "woo-axept-server");
    $country_codes["417"]   =  __("Kyrgyzstan", "woo-axept-server");
    $country_codes["418"]   =  __("Laos", "woo-axept-server");
    $country_codes["422"]   =  __("Lebanon", "woo-axept-server");    
    $country_codes["426"]   =  __("Lesotho", "woo-axept-server");
    $country_codes["428"]   =  __("Latvia", "woo-axept-server");
    $country_codes["430"]   =  __("Liberia", "woo-axept-server");
    $country_codes["434"]   =  __("Libya", "woo-axept-server");
    $country_codes["438"]   =  __("Liechtenstein", "woo-axept-server");    
    $country_codes["440"]   =  __("Lithuania", "woo-axept-server");
    $country_codes["442"]   =  __("Luxembourg", "woo-axept-server");
    $country_codes["446"]   =  __("Macau", "woo-axept-server");
    $country_codes["450"]   =  __("Madagascar", "woo-axept-server");
    $country_codes["454"]   =  __("Malawi", "woo-axept-server");
    $country_codes["458"]   =  __("Malaysia", "woo-axept-server");
    $country_codes["462"]   =  __("Maldives", "woo-axept-server");    
    $country_codes["466"]   =  __("Mali", "woo-axept-server");
    $country_codes["470"]   =  __("Malta", "woo-axept-server");
    $country_codes["474"]   =  __("Martinique", "woo-axept-server");
    $country_codes["478"]   =  __("Mauritania", "woo-axept-server");
    $country_codes["480"]   =  __("Mauritius", "woo-axept-server");
    $country_codes["484"]   =  __("Mexico", "woo-axept-server");
    $country_codes["492"]   =  __("Monaco", "woo-axept-server");    
    $country_codes["496"]   =  __("Mongolia", "woo-axept-server");
    $country_codes["498"]   =  __("Moldova", "woo-axept-server");
    $country_codes["499"]   =  __("Montenegro", "woo-axept-server");
    $country_codes["500"]   =  __("Montserrat", "woo-axept-server");
    $country_codes["504"]   =  __("Morocco", "woo-axept-server");    
    $country_codes["508"]   =  __("Mozambique", "woo-axept-server");
    $country_codes["512"]   =  __("Oman", "woo-axept-server");
    $country_codes["516"]   =  __("Namibia", "woo-axept-server");
    $country_codes["520"]   =  __("Nauru", "woo-axept-server");
    $country_codes["524"]   =  __("Nepal", "woo-axept-server");
    $country_codes["528"]   =  __("Netherlands", "woo-axept-server");
    $country_codes["531"]   =  __("Curacao", "woo-axept-server");    
    $country_codes["533"]   =  __("Aruba", "woo-axept-server");
    $country_codes["534"]   =  __("Sint Maarten", "woo-axept-server");
    $country_codes["540"]   =  __("New Caledonia", "woo-axept-server");
    $country_codes["548"]   =  __("Vanuatu", "woo-axept-server");
    $country_codes["554"]   =  __("New Zealand", "woo-axept-server");
    $country_codes["558"]   =  __("Nicaragua", "woo-axept-server");
    $country_codes["562"]   =  __("Niger", "woo-axept-server");    
    $country_codes["566"]   =  __("Nigeria", "woo-axept-server");
    $country_codes["570"]   =  __("Niue", "woo-axept-server");
    $country_codes["574"]   =  __("Norfolk Island", "woo-axept-server");
    $country_codes["578"]   =  __("Norway", "woo-axept-server");
    $country_codes["580"]   =  __("Northern Mariana Islands", "woo-axept-server");    
    $country_codes["581"]   =  __("United States Minor Outlying Islands", "woo-axept-server");
    $country_codes["583"]   =  __("Micronesia, Federated States of", "woo-axept-server");
    $country_codes["584"]   =  __("Marshall Islands", "woo-axept-server");
    $country_codes["585"]   =  __("Palau", "woo-axept-server");
    $country_codes["586"]   =  __("Pakistan", "woo-axept-server");
    $country_codes["591"]   =  __("Panama", "woo-axept-server");
    $country_codes["598"]   =  __("Papua New Guinea", "woo-axept-server");    
    $country_codes["600"]   =  __("Paraguay", "woo-axept-server");
    $country_codes["604"]   =  __("Peru", "woo-axept-server");
    $country_codes["608"]   =  __("Philippines", "woo-axept-server");
    $country_codes["612"]   =  __("Pitcairn Islands", "woo-axept-server");
    $country_codes["616"]   =  __("Poland", "woo-axept-server");
    $country_codes["620"]   =  __("Portugal", "woo-axept-server");
    $country_codes["624"]   =  __("Guinea-Bissau", "woo-axept-server");    
    $country_codes["626"]   =  __("Timor-Leste", "woo-axept-server");
    $country_codes["630"]   =  __("Puerto Rico", "woo-axept-server");
    $country_codes["634"]   =  __("Qatar", "woo-axept-server");
    $country_codes["638"]   =  __("Reunion", "woo-axept-server");
    $country_codes["642"]   =  __("Romania", "woo-axept-server");    
    $country_codes["643"]   =  __("Russia", "woo-axept-server");
    $country_codes["646"]   =  __("Rwanda", "woo-axept-server");
    $country_codes["652"]   =  __("Saint Barthelemy", "woo-axept-server");
    $country_codes["654"]   =  __("Saint Helena, Ascension, and Tristan da Cunha", "woo-axept-server");
    $country_codes["659"]   =  __("Saint Kitts and Nevis", "woo-axept-server");
    $country_codes["660"]   =  __("Anguilla", "woo-axept-server");
    $country_codes["662"]   =  __("Saint Lucia", "woo-axept-server");    
    $country_codes["663"]   =  __("Saint Martin", "woo-axept-server");
    $country_codes["666"]   =  __("Saint Pierre and Miquelon", "woo-axept-server");
    $country_codes["670"]   =  __("Saint Vincent and the Grenadines", "woo-axept-server");
    $country_codes["674"]   =  __("San Marino", "woo-axept-server");
    $country_codes["678"]   =  __("Sao Tome and Principe", "woo-axept-server");
    $country_codes["682"]   =  __("Saudi Arabia", "woo-axept-server");
    $country_codes["686"]   =  __("Senegal", "woo-axept-server");    
    $country_codes["688"]   =  __("Serbia", "woo-axept-server");
    $country_codes["690"]   =  __("Seychelles", "woo-axept-server");
    $country_codes["694"]   =  __("Sierra Leone", "woo-axept-server");
    $country_codes["702"]   =  __("Singapore", "woo-axept-server");
    $country_codes["703"]   =  __("Slovakia", "woo-axept-server");    
    $country_codes["704"]   =  __("Vietnam", "woo-axept-server");
    $country_codes["705"]   =  __("Slovenia", "woo-axept-server");
    $country_codes["706"]   =  __("Somalia", "woo-axept-server");
    $country_codes["710"]   =  __("South Africa", "woo-axept-server");
    $country_codes["716"]   =  __("Zimbabwe", "woo-axept-server");
    $country_codes["724"]   =  __("Spain", "woo-axept-server");
    $country_codes["728"]   =  __("South Sudan", "woo-axept-server");    
    $country_codes["729"]   =  __("Sudan", "woo-axept-server");
    $country_codes["732"]   =  __("Western Sahara", "woo-axept-server");
    $country_codes["740"]   =  __("Suriname", "woo-axept-server");
    $country_codes["744"]   =  __("Svalbard", "woo-axept-server");
    $country_codes["748"]   =  __("Swaziland", "woo-axept-server");
    $country_codes["752"]   =  __("Sweden", "woo-axept-server");
    $country_codes["756"]   =  __("Switzerland", "woo-axept-server");    
    $country_codes["760"]   =  __("Syria", "woo-axept-server");
    $country_codes["762"]   =  __("Tajikistan", "woo-axept-server");
    $country_codes["764"]   =  __("Thailand", "woo-axept-server");
    $country_codes["768"]   =  __("Togo", "woo-axept-server");
    $country_codes["772"]   =  __("Tokelau", "woo-axept-server");    
    $country_codes["776"]   =  __("Tonga", "woo-axept-server");
    $country_codes["780"]   =  __("Trinidad and Tobago", "woo-axept-server");
    $country_codes["784"]   =  __("United Arab Emirates", "woo-axept-server");
    $country_codes["788"]   =  __("Tunisia", "woo-axept-server");
    $country_codes["792"]   =  __("Turkey", "woo-axept-server");
    $country_codes["795"]   =  __("Turkmenistan", "woo-axept-server");
    $country_codes["796"]   =  __("Turks and Caicos Islands", "woo-axept-server");    
    $country_codes["798"]   =  __("Tuvalu", "woo-axept-server");
    $country_codes["800"]   =  __("Uganda", "woo-axept-server");
    $country_codes["804"]   =  __("Ukraine", "woo-axept-server");
    $country_codes["807"]   =  __("Macedonia", "woo-axept-server");
    $country_codes["818"]   =  __("Egypt", "woo-axept-server");
    $country_codes["826"]   =  __("United Kingdom", "woo-axept-server");
    $country_codes["831"]   =  __("Guernsey", "woo-axept-server");    
    $country_codes["832"]   =  __("Jersey", "woo-axept-server");
    $country_codes["833"]   =  __("Isle of Man", "woo-axept-server");
    $country_codes["834"]   =  __("Tanzania", "woo-axept-server");
    $country_codes["840"]   =  __("United States", "woo-axept-server");
    $country_codes["850"]   =  __("Virgin Islands", "woo-axept-server");    
    $country_codes["854"]   =  __("Burkina Faso", "woo-axept-server");
    $country_codes["858"]   =  __("Uruguay", "woo-axept-server");
    $country_codes["860"]   =  __("Uzbekistan", "woo-axept-server");
    $country_codes["862"]   =  __("Venezuela", "woo-axept-server");
    $country_codes["876"]   =  __("Wallis and Futuna", "woo-axept-server");
    $country_codes["882"]   =  __("Samoa", "woo-axept-server");
    $country_codes["887"]   =  __("Yemen", "woo-axept-server");    
    $country_codes["894"]   =  __("Zambia", "woo-axept-server");
    $country_codes["900"]   =  __("Kosova", "woo-axept-server");
    $country_codes["968"]   =  __("Suriname", "woo-axept-server");
    
    return $country_codes;
}

function getCscMatrixOptions(){
	$array		 	= 	array();
  $array[0]  	 =  	__("None (all results will be accepted)", "woo-axept-server");
	$array[2]   	=  	__("NotProvided", "woo-axept-server");
	$array[4]   	=  	__("NotChecked", "woo-axept-server");
	$array[6]   	=  	__("NotProvided and a NotChecked", "woo-axept-server");
	$array[8]   	=  	__("Matched", "woo-axept-server");   
	$array[16]   	=  	__("NotMatched", "woo-axept-server");   
	$array[32]   	=  	__("PartialMatch", "woo-axept-server");   
	return $array;
}   
function getAvsHouseMatrixOptions(){
	$array		 	= 	array();
  $array[0]  	 =  	__("None (all results will be accepted)", "woo-axept-server");
	$array[2]   	=  	__("NotProvided", "woo-axept-server");
	$array[4]   	=  	__("NotChecked", "woo-axept-server");
	$array[6]   	=  	__("NotProvided and a NotChecked", "woo-axept-server");
	$array[8]   	=  	__("Matched", "woo-axept-server");   
	$array[16]   	=  	__("NotMatched", "woo-axept-server");   
	$array[32]   	=  	__("PartialMatch", "woo-axept-server");   
	return $array;
}    
function getAvsPostCodeMatrixOptions(){
	$array		 	= 	array();
  $array[0]  	 =  	__("None (all results will be accepted)", "woo-axept-server");
	$array[2]   	=  	__("NotProvided", "woo-axept-server");
	$array[4]   	=  	__("NotChecked", "woo-axept-server");
	$array[6]   	=  	__("NotProvided and a NotChecked", "woo-axept-server");
	$array[8]   	=  	__("Matched", "woo-axept-server");   
	$array[16]   	=  	__("NotMatched", "woo-axept-server");   
	$array[32]   	=  	__("PartialMatch", "woo-axept-server");   
	return $array;
}    
function getPaMatrixOptions(){
	$array		 	= 	array();
  $array[0]  	 =  	__("Reject None (all results will be accepted)", "woo-axept-server");
	$array[1]   	=  	__("Reject U on Enrolment - Enrolment process did not complete", "woo-axept-server");
	$array[2]   	=  	__("Reject U on Authentication - Authentication did not complete", "woo-axept-server");
	return $array;
}    
function getCurrencyCodes(){
    
    $currency_codes = array();
    
    $currency_codes["784"]   =  __("United Arab Emirates dirham (AED)", "woo-axept-server");
    $currency_codes["971"]   =  __("Afghan afghani (AFN)", "woo-axept-server");
    $currency_codes["008"]   =  __("Albanian lek (ALL)", "woo-axept-server");
    $currency_codes["051"]   =  __("Armenian dram (AMD)", "woo-axept-server");           
    $currency_codes["532"]   =  __("Netherlands Antillean guilder(ANG)", "woo-axept-server");
    $currency_codes["973"]   =  __("Angolan kwanza(AOA)", "woo-axept-server");
    $currency_codes["032"]   =  __("Argentine peso(ARS)", "woo-axept-server");
    $currency_codes["036"]   =  __("Australian dollar(AUD)", "woo-axept-server");
    $currency_codes["533"]   =  __("Aruban florin (AWG)", "woo-axept-server");
    $currency_codes["944"]   =  __("Azerbaijani manat (AZN)", "woo-axept-server");
    $currency_codes["977"]   =  __("Bosnia and Herzegovina convertible mark (BAM)", "woo-axept-server");
    $currency_codes["052"]   =  __("Barbados dollar(BBD)", "woo-axept-server");           
    $currency_codes["050"]   =  __("Bangladeshi taka(BDT)", "woo-axept-server");
    $currency_codes["975"]   =  __("Bulgarian lev(BGN)", "woo-axept-server");
    $currency_codes["048"]   =  __("Bahraini dinar(BHD)", "woo-axept-server");
    $currency_codes["108"]   =  __("Burundian franc(BIF)", "woo-axept-server");
    $currency_codes["060"]   =  __("Bermudian dollar (BMD)", "woo-axept-server");
    $currency_codes["096"]   =  __("Brunei dollar (BND)", "woo-axept-server");
    $currency_codes["068"]   =  __("Boliviano(BOB)", "woo-axept-server");
    $currency_codes["984"]   =  __("Bolivian Mvdol (funds code) (BOV)", "woo-axept-server");           
    $currency_codes["986"]   =  __("Brazilian real(BRL)", "woo-axept-server");
    $currency_codes["044"]   =  __("Bahamian dollar(BSD)", "woo-axept-server");
    $currency_codes["064"]   =  __("Bhutanese ngultrum(BTN)", "woo-axept-server");
    $currency_codes["072"]   =  __("Botswana pula(BWP)", "woo-axept-server");
    $currency_codes["933"]   =  __("Belarusian ruble (BYN)", "woo-axept-server");
    $currency_codes["974"]   =  __("Belarusian ruble (BYR)", "woo-axept-server");
    $currency_codes["084"]   =  __("Belize dollar (BZD)", "woo-axept-server");
    $currency_codes["124"]   =  __("Canadian dollar(CAD)", "woo-axept-server");           
    $currency_codes["976"]   =  __("Congolese franc(CDF)", "woo-axept-server");
    $currency_codes["947"]   =  __("WIR Euro (complementary currency)(CHE)", "woo-axept-server");
    $currency_codes["756"]   =  __("Swiss franc(CHF)", "woo-axept-server");
    $currency_codes["948"]   =  __("WIR Franc (complementary currency)(CHW)", "woo-axept-server");
    $currency_codes["990"]   =  __("Unidad de Fomento (funds code) (CLF)", "woo-axept-server");
    $currency_codes["152"]   =  __("Chilean peso(CLP)", "woo-axept-server");
    $currency_codes["156"]   =  __("Chinese yuan(CNY)", "woo-axept-server");
    $currency_codes["170"]   =  __("Colombian peso (COP)", "woo-axept-server");           
    $currency_codes["970"]   =  __("Unidad de Valor Real (UVR) (funds code)(COU)", "woo-axept-server");
    $currency_codes["188"]   =  __("Costa Rican colon(CRC)", "woo-axept-server");
    $currency_codes["931"]   =  __("Cuban convertible peso(CUC)", "woo-axept-server");
    $currency_codes["192"]   =  __("Cuban peso(CUP)", "woo-axept-server");
    $currency_codes["132"]   =  __("Cape Verde escudo (CVE)", "woo-axept-server");
    $currency_codes["203"]   =  __("Czech koruna (CZK)", "woo-axept-server");
    $currency_codes["262"]   =  __("Djiboutian franc (DJF)", "woo-axept-server");
    $currency_codes["208"]   =  __("Danish krone(DKK)", "woo-axept-server");           
    $currency_codes["214"]   =  __("Dominican peso(DOP)", "woo-axept-server");
    $currency_codes["012"]   =  __("Algerian dinar(DZD)", "woo-axept-server");
    $currency_codes["818"]   =  __("Egyptian pound(EGP)", "woo-axept-server");
    $currency_codes["232"]   =  __("Eritrean nakfa(ERN)", "woo-axept-server");  
    $currency_codes["230"]   =  __("Ethiopian birr(ETB)", "woo-axept-server");
    $currency_codes["978"]   =  __("Euro(EUR)", "woo-axept-server");
    $currency_codes["242"]   =  __("Fiji dollar(FJD)", "woo-axept-server");
    $currency_codes["238"]   =  __("Falkland Islands pound (FKP)", "woo-axept-server");
    $currency_codes["826"]   =  __("Pound sterling (GBP)", "woo-axept-server");
    $currency_codes["981"]   =  __("Georgian lari (GEL)", "woo-axept-server");
    $currency_codes["936"]   =  __("Ghanaian cedi(GHS)", "woo-axept-server");           
    $currency_codes["292"]   =  __("Gibraltar pound(GIP)", "woo-axept-server");
    $currency_codes["270"]   =  __("Gambian dalasi(GMD)", "woo-axept-server");
    $currency_codes["324"]   =  __("Guinean franc(GNF)", "woo-axept-server");
    $currency_codes["320"]   =  __("Guatemalan quetzal(GTQ)", "woo-axept-server");
    $currency_codes["328"]   =  __("Guyanese dollar(GYD)", "woo-axept-server");
    $currency_codes["344"]   =  __("Hong Kong dollar(HKD)", "woo-axept-server");
    $currency_codes["340"]   =  __("Honduran lempira(HNL)", "woo-axept-server");
    $currency_codes["191"]   =  __("Croatian kuna (HRK)", "woo-axept-server");
    $currency_codes["332"]   =  __("Haitian gourde (HTG)", "woo-axept-server");
    $currency_codes["348"]   =  __("Hungarian forint (HUF)", "woo-axept-server");
    $currency_codes["360"]   =  __("Indonesian rupiah(IDR)", "woo-axept-server");           
    $currency_codes["376"]   =  __("Israeli new shekel(ILS)", "woo-axept-server");
    $currency_codes["356"]   =  __("Indian rupee(INR)", "woo-axept-server");
    $currency_codes["368"]   =  __("Iraqi dinar(IQD)", "woo-axept-server");
    $currency_codes["364"]   =  __("Iranian rial(IRR)", "woo-axept-server");	
    $currency_codes["352"]   =  __("Icelandic króna(ISK)", "woo-axept-server");
    $currency_codes["388"]   =  __("Jamaican dollar(JMD)", "woo-axept-server");
    $currency_codes["400"]   =  __("Jordanian dinar(JOD)", "woo-axept-server");
    $currency_codes["392"]   =  __("Japanese yen(JPY)", "woo-axept-server");
    $currency_codes["404"]   =  __("Kenyan shilling(KES)", "woo-axept-server");
    $currency_codes["417"]   =  __("Kyrgyzstani som(KGS)", "woo-axept-server");
    $currency_codes["116"]   =  __("Cambodian riel(KHR)", "woo-axept-server");
    $currency_codes["174"]   =  __("Comoro franc (KMF)", "woo-axept-server");
    $currency_codes["408"]   =  __("North Korean won(KPW)", "woo-axept-server");
    $currency_codes["410"]   =  __("South Korean won (KRW)", "woo-axept-server");
    $currency_codes["414"]   =  __("Kuwaiti dinar(KWD)", "woo-axept-server");           
    $currency_codes["136"]   =  __("Cayman Islands dollar(KYD)", "woo-axept-server");
    $currency_codes["398"]   =  __("Kazakhstani tenge(KZT)", "woo-axept-server");
    $currency_codes["418"]   =  __("Lao kip(LAK)", "woo-axept-server");
    $currency_codes["422"]   =  __("Lebanese pound(LBP)", "woo-axept-server");
    $currency_codes["144"]   =  __("Sri Lankan rupee(LKR)", "woo-axept-server");
    $currency_codes["430"]   =  __("Liberian dollar (LRD)", "woo-axept-server");
    $currency_codes["426"]   =  __("Lesotho loti (LSL)", "woo-axept-server");
    $currency_codes["434"]   =  __("Libyan dinar (LYD)", "woo-axept-server");
    $currency_codes["504"]   =  __("Moroccan dirham(MAD)", "woo-axept-server");           
    $currency_codes["498"]   =  __("Moldovan leu(MDL)", "woo-axept-server");
    $currency_codes["969"]   =  __("Malagasy ariary(MGA)", "woo-axept-server");
    $currency_codes["807"]   =  __("Macedonian denar(MKD)", "woo-axept-server");
    $currency_codes["104"]   =  __("Myanmar kyat(MMK)", "woo-axept-server");	
    $currency_codes["496"]   =  __("Mongolian tögrög(MNT)", "woo-axept-server");
    $currency_codes["446"]   =  __("Macanese pataca(MOP)", "woo-axept-server");
    $currency_codes["478"]   =  __("Mauritanian ouguiya(MRO)", "woo-axept-server");
    $currency_codes["480"]   =  __("Mauritian rupee(MUR)", "woo-axept-server");
    $currency_codes["462"]   =  __("Maldivian rufiyaa(MVR)", "woo-axept-server");
    $currency_codes["454"]   =  __("Malawian kwacha(MWK)", "woo-axept-server");
    $currency_codes["484"]   =  __("Mexican peso(MXN)", "woo-axept-server");
    $currency_codes["979"]   =  __("Mexican Unidad de Inversion (UDI) (funds code) (MXV)", "woo-axept-server");
    $currency_codes["458"]   =  __("Malaysian ringgit(MYR)", "woo-axept-server");
    $currency_codes["943"]   =  __("Mozambican metical (MZN)", "woo-axept-server");
    $currency_codes["516"]   =  __("Namibian dollar(NAD)", "woo-axept-server");           
    $currency_codes["566"]   =  __("Nigerian naira(NGN)", "woo-axept-server");
    $currency_codes["558"]   =  __("Nicaraguan córdoba(NIO)", "woo-axept-server");
    $currency_codes["578"]   =  __("Norwegian krone(NOK)", "woo-axept-server");
    $currency_codes["524"]   =  __("Nepalese rupee(NPR)", "woo-axept-server");    	
    $currency_codes["554"]   =  __("New Zealand dollar (NZD)", "woo-axept-server");
    $currency_codes["512"]   =  __("Omani rial (OMR)", "woo-axept-server");
    $currency_codes["590"]   =  __("Panamanian balboa(PAB)", "woo-axept-server");           
    $currency_codes["604"]   =  __("Peruvian Sol(PEN)", "woo-axept-server");
    $currency_codes["598"]   =  __("Papua New Guinean kina(PGK)", "woo-axept-server");
    $currency_codes["608"]   =  __("Philippine peso(PHP)", "woo-axept-server");
    $currency_codes["586"]   =  __("Pakistani rupee(PKR)", "woo-axept-server");
    $currency_codes["985"]   =  __("Polish złoty(PLN)", "woo-axept-server");
    $currency_codes["600"]   =  __("Paraguayan guaraní(PYG)", "woo-axept-server");
    $currency_codes["634"]   =  __("Qatari riyal(QAR)", "woo-axept-server");
    $currency_codes["946"]   =  __("Romanian leu(RON)", "woo-axept-server");
    $currency_codes["941"]   =  __("Serbian dinar (RSD)", "woo-axept-server");
    $currency_codes["643"]   =  __("Russian ruble (RUB)", "woo-axept-server");
    $currency_codes["646"]   =  __("Rwandan franc(RWF)", "woo-axept-server");           
    $currency_codes["682"]   =  __("Saudi riyal	(SAR)", "woo-axept-server");		
    $currency_codes["090"]   =  __("Solomon Islands dollar(SBD)", "woo-axept-server");
    $currency_codes["690"]   =  __("Seychelles rupee(SCR)", "woo-axept-server");
    $currency_codes["938"]   =  __("Sudanese pound(SDG)", "woo-axept-server");
    $currency_codes["752"]   =  __("Swedish krona/kronor(SEK)", "woo-axept-server");
    $currency_codes["702"]   =  __("Singapore dollar(SGD)", "woo-axept-server");
    $currency_codes["654"]   =  __("Saint Helena pound(SHP)", "woo-axept-server");
    $currency_codes["694"]   =  __("Sierra Leonean leone(SLL)", "woo-axept-server");
    $currency_codes["706"]   =  __("Somali shilling(SOS)", "woo-axept-server");
    $currency_codes["968"]   =  __("Surinamese dollar(SRD)", "woo-axept-server");
    $currency_codes["728"]   =  __("South Sudanese pound (SSP)", "woo-axept-server");
    $currency_codes["678"]   =  __("São Tomé and Príncipe dobra(STD)", "woo-axept-server");           
    $currency_codes["760"]   =  __("Syrian pound(SYP)", "woo-axept-server");
    $currency_codes["748"]   =  __("Swazi lilangeni(SZL)", "woo-axept-server");
    $currency_codes["764"]   =  __("Thai baht(THB)", "woo-axept-server");
    $currency_codes["972"]   =  __("Tajikistani somoni(TJS)", "woo-axept-server");
    $currency_codes["934"]   =  __("Turkmenistani manat(TMT)", "woo-axept-server");   
    $currency_codes["788"]   =  __("Tunisian dinar (TND)", "woo-axept-server");
    $currency_codes["776"]   =  __("Tongan paʻanga (TOP)", "woo-axept-server");
    $currency_codes["949"]   =  __("Turkish lira(TRY)", "woo-axept-server");           
    $currency_codes["780"]   =  __("Trinidad and Tobago dollar(TTD)", "woo-axept-server");
    $currency_codes["901"]   =  __("New Taiwan dollar(TWD)", "woo-axept-server");
    $currency_codes["834"]   =  __("Tanzanian shilling(TZS)", "woo-axept-server");
    $currency_codes["980"]   =  __("Ukrainian hryvnia(UAH)", "woo-axept-server");
    $currency_codes["800"]   =  __("Ugandan shilling(UGX)", "woo-axept-server");
    $currency_codes["840"]   =  __("United States dollar(USD)", "woo-axept-server");
    $currency_codes["997"]   =  __("United States dollar (next day) (funds code)(USN)", "woo-axept-server");
    $currency_codes["998"]   =  __("United States dollar (same day) (funds code)(USS)", "woo-axept-server");
    $currency_codes["940"]   =  __("Uruguay Peso en Unidades Indexadas (URUIURUI) (funds code) (UYI)", "woo-axept-server");
    $currency_codes["858"]   =  __("Uruguayan peso(UYU)", "woo-axept-server");
    $currency_codes["860"]   =  __("Uzbekistan som(UZS)", "woo-axept-server");           
    $currency_codes["937"]   =  __("Venezuelan bolívar	(VEF)", "woo-axept-server");	
    $currency_codes["704"]   =  __("Vietnamese dong(VND)", "woo-axept-server");
    $currency_codes["548"]   =  __("Vanuatu vatu(VUV)", "woo-axept-server");
    $currency_codes["882"]   =  __("Samoan tala(WST)", "woo-axept-server");
    $currency_codes["950"]   =  __("CFA franc BEAC(XAF)", "woo-axept-server");
    $currency_codes["961"]   =  __("Silver (one troy ounce)(XAG)", "woo-axept-server");
    $currency_codes["959"]   =  __("Gold (one troy ounce)(XAU)", "woo-axept-server");
    $currency_codes["955"]   =  __("European Composite Unit(EURCO) (bond market unit)(XBA)", "woo-axept-server");	
    $currency_codes["956"]   =  __("European Monetary Unit(E.M.U.-6) (bond market unit) (XBB)", "woo-axept-server");
    $currency_codes["957"]   =  __("European Unit of Account 9(E.U.A.-9) (bond market unit) (XBC)", "woo-axept-server");
    $currency_codes["958"]   =  __("European Unit of Account 17(E.U.A.-17) (bond market unit)(XBD)", "woo-axept-server");           
    $currency_codes["951"]   =  __("East Caribbean dollar(XCD)", "woo-axept-server");		
    $currency_codes["960"]   =  __("Special drawing rights (XDR)", "woo-axept-server");
    $currency_codes["Nil"]   =  __("UIC franc (special settlement currency)(XFU)", "woo-axept-server");
    $currency_codes["952"]   =  __("CFA franc BCEAO(XOF)", "woo-axept-server");
    $currency_codes["964"]   =  __("Palladium (one troy ounce)(XPD)", "woo-axept-server");
    $currency_codes["953"]   =  __("CFP franc (franc Pacifique)(XPF)", "woo-axept-server");
    $currency_codes["962"]   =  __("Platinum (one troy ounce)(XPT)", "woo-axept-server");
    $currency_codes["994"]   =  __("SUCRE(XSU)", "woo-axept-server");
    $currency_codes["963"]   =  __("Code reserved for testing purposes(XTS)", "woo-axept-server");
    $currency_codes["965"]   =  __("ADB Unit of Account(XUA)", "woo-axept-server");
    $currency_codes["999"]   =  __("No currency (XXX)", "woo-axept-server");
    $currency_codes["886"]   =  __("Yemeni rial(YER)", "woo-axept-server"); 	
    $currency_codes["710"]   =  __("South African rand (ZAR)", "woo-axept-server");
    $currency_codes["967"]   =  __("Zambian kwacha  (ZMW)",  "woo-axept-server");	
     
       
    
    return $currency_codes;
}


add_action( 'woocommerce_before_checkout_form', 'skyverge_add_checkout_success', 9 );
function skyverge_add_checkout_success() {
    
    if(!empty(sanitize_text_field($_GET['gateway_status']))){
        $gateway_status = sanitize_text_field($_GET['gateway_status']);
        
        if($gateway_status == 'declined'){            
            //wc_add_notice( __( 'Transaction Declined by the Acquirer.', 'woocommerce' ), 'error' );
            wc_add_notice( __( 'Sorry, we have been unable to process your payment at this time. Please try again or contact our Customer Services team.', 'woocommerce' ), 'error' );
        }
        elseif($gateway_status == 'cancelled'){
            //wc_add_notice( __( 'Transaction cancelled by cardholders.', 'woocommerce' ), 'error' );            
            wc_add_notice( __( 'Sorry, we have been unable to process your payment at this time. Please try again or contact our Customer Services team.', 'woocommerce' ), 'error' );
        }
        elseif($gateway_status == 'rejected'){
            //wc_add_notice( __( 'Transaction Rejected due to AVS/CSC, Payer Authentication results or cardholder error.', 'woocommerce' ), 'error' );            
            wc_add_notice( __( 'Sorry, we have been unable to process your payment at this time. Please try again or contact our Customer Services team.', 'woocommerce' ), 'error' );
        }
    }
}