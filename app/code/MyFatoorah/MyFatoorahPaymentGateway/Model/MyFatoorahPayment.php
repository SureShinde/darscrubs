<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Model;

use MyFatoorah\MyFatoorahPaymentGateway\Helper\Crypto;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;

class MyFatoorahPayment extends \Magento\Payment\Model\Method\AbstractMethod implements HandlerInterface {
    public $_isGateway = true;
    public $_canRefund = true;
    public $_canRefundInvoicePartial = true;
    public $_canCapture = true;
    public $_canCapturePartial = true;
    public $_scopeConfig;

    public function __construct(
            \Magento\Framework\Model\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
            \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
            \Magento\Payment\Helper\Data $paymentData,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Payment\Model\Method\Logger $logger
    ) {
        parent::__construct(
                $context,
                $registry,
                $extensionFactory,
                $customAttributeFactory,
                $paymentData,
                $scopeConfig,
                $logger
        );
        $this->_scopeConfig = $scopeConfig;
    }
/*
    public function handle2( array $handlingSubject, array $response ) {
        $refund_url      = $response['GATEWAY_REFUND_GATEWAY_URL'];
        $merchant_number = $response['GATEWAY_MERCHANT_ID'];
        $apiKey          = $response['GATEWAY_API_KEY'];

        $refund_amount = $handlingSubject['amount'];
        $payment       = $handlingSubject['payment']->getPayment();

        if ( empty( $payment ) || empty( $payment->getData( 'creditmemo' ) ) ) {
            throw new LocalizedException(
                __( 'We can\'t issue a refund transaction because there is no capture transaction.' )
            );
        }

        $transaction_id = $payment->getData()['creditmemo']->getData( 'invoice' )->getData( 'transaction_id' );
        $refund_details = array(
            "x_merchant_number" => $merchant_number,
            "x_purchase_number" => $transaction_id,
            "x_amount"          => $refund_amount,
            "x_reason"          => "Refund"
        );

        $refund_signature            = Crypto::generateSignature( $refund_details, $apiKey );
        $refund_details['signature'] = $refund_signature;

        $json = json_encode( $refund_details );

        // Do refunding POST request using curl
        $curl = curl_init( $refund_url );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $json );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_HEADER, 1 );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-type: application/json" ) );
        $response = curl_exec( $curl );

        // split and parse header and body
        $header_size         = curl_getinfo( $curl, CURLINFO_HEADER_SIZE );
        $header_string       = substr( $response, 0, $header_size );
        $body                = substr( $response, $header_size );
        $header_rows         = explode( PHP_EOL, $header_string );
        $header_rows_trimmed = array_map( 'trim', $header_rows );
        $parsed_header       = ( $this->parseHeaders( $header_rows_trimmed ) );

        curl_close( $curl );

        if ( $parsed_header['response_code'] == '204' ) {
            return $this;
        } elseif ( $parsed_header['response_code'] == '401' ) {
            $error_message = 'MyFatoorah refunding error: Failed Signature Check when communicating with the MyFatoorah gateway.';
        } elseif ( $parsed_header['response_code'] == '400' ) {
            $return_message         = json_decode( $body, true )['Message'];
            $return_message_explain = '';
            if ( $return_message == "MERR0001" ) {
                $return_message_explain = ' (API Key Not found)';
            } elseif ( $return_message == "MERR0003" ) {
                $return_message_explain = ' (Refund Failed)';
            } elseif ( $return_message == "MERR0004" ) {
                $return_message_explain = ' (Invalid Request)';
            }
            $error_message = 'MyFatoorah refunding error with returned message from gateway: ' . $return_message . $return_message_explain;
        } else {
            $error_message = "MyFatoorah refunding failed with unknown error.";
        }
        $this->_logger->error( __( $error_message ) );
        throw new LocalizedException( __( $error_message ) );
    }

    function parseHeaders( $headers ) {
        $head = array();
        foreach ( $headers as $k => $v ) {
            $t = explode( ':', $v, 2 );
            if ( isset( $t[1] ) ) {
                $head[ trim( $t[0] ) ] = trim( $t[1] );
            } else {
                $head[] = $v;
                if ( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out ) ) {
                    $head['response_code'] = intval( $out[1] );
                }
            }
        }

        return $head;
    }
*/
    public function handle(array $handlingSubject, array $response) {

        //?????????whos calling handel
        //??????????? //??????????????????what is that?  $merchant_number = $response['GATEWAY_MERCHANT_ID'];  fix the 1983
        $refund_url = $response['GATEWAY_REFUND_GATEWAY_URL'];
        $apiKey     = $response['GATEWAY_API_KEY'];

        $payment = $handlingSubject['payment']->getPayment();
        if (empty($payment) || empty($payment->getData('creditmemo'))) {
            throw new LocalizedException(__('We can\'t issue a refund transaction because there is no capture transaction.'));
        }

        $invoice = $payment->getData()['creditmemo']->getData('invoice');
        $transaction_id = $invoice->getData('transaction_id');
        $order_id = $invoice->getData('order_id');

        $refund_details = array(
            'KeyType'                 => 'InvoiceId',
            'Key'                     => $transaction_id,
            'RefundChargeOnCustomer'  => false,
            'ServiceChargeOnCustomer' => false,
            'Amount'                  => $handlingSubject['amount'],
            'Comment'                 => 'Refund',
        );

        $json = $this->callAPI($refund_url, $refund_details, $order_id, 'Make Refund', $apiKey);

        if ($json->IsSuccess == true) {
            return $this;
        }

        $this->_logger->error(__($json->Data->ErrorMessage));
        throw new LocalizedException(__($json->Data->ErrorMessage));
    }

    public function callAPI($url, $postFields, $orderId, $function, $apiKey) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL           => $url,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS    => json_encode($postFields),
            CURLOPT_HTTPHEADER    => array("Authorization: Bearer $apiKey", 'Content-Type: application/json'),
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
//???????$logger$logger$logger
        $log = new \Zend\Log\Logger();
        $log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));
        $log->info('----------------------------------------------------------------------------------------------------------------------------------------------------------------');
        $log->info($function . ' ----- Order# ' . $orderId . ' Response : ' . $res);

        if ($err) {
            $log->info($function . ' ----- Order# ' . $orderId . ' - cURL Error #:' . $err);
            return (object) array('IsSuccess' => false, 'Data' => (object) array('ErrorMessage' => $function . ' - cURL Error #:' . $err));
        }

        $json = json_decode($res);
        if (!isset($json->IsSuccess) || $json->IsSuccess == null || $json->IsSuccess == false) {
            if (isset($json->ValidationErrors)) {
                $err_message = implode('<br/>', array_column($json->ValidationErrors, 'Error'));

                /* $blogDatas = array_column($json->ValidationErrors, 'Error', 'Name');
                  $err_message = implode(', ', array_map(function ($k, $v) { return "$k: $v"; }, array_keys($blogDatas), array_values($blogDatas))); */
            } else if (isset($json->Message)) {
                $err_message = $json->Message;
            }

            $log->info($function . ' ----- Order# ' . $orderId . ' Error : ' . $err_message);
            return (object) array('IsSuccess' => false, 'Data' => (object) array('ErrorMessage' => $err_message));
        }
        return $json;
    }

}
