<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Model;

use MyFatoorah\MyFatoorahPaymentGateway\Api\MFOrderManagementInterface;

//use Magento\Sales\Model\Order;
//use MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout\AbstractAction;

class MFOrderManagement implements MFOrderManagementInterface {

    /**
     * Returns payment status
     *
     * @api
     * @param int $cartId cart ID.
     * @param int $billingAddressId billing Address ID.
     * @param string $gateway gateway.
     * @return mixed.
     */
    public $token;

    public function checkout($cartId, $billingAddressId, $gateway = null) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorahAPI.log');
        $this->log = new \Zend\Log\Logger();
        $this->log->addWriter($writer);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quote = $objectManager->create('Magento\Quote\Model\Quote')->loadByIdWithoutStore($cartId);
        if (!$quote->getCustomerId()) {
            return '{"error": {"param": "cartId","message": "cart ID does not exist "}}';
        }

        $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingAddressId);
        $addressArray = $address->getData();

        if (empty($addressArray)) {
            return '{"error": {"param": "addressId","message": "Address does not exist "}}';
        }

        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerData = $customerSession->getCustomer()->getData(); //get all data of customerData
        $Firstname = $customerData['firstname'];
        $Lastname = $customerData['lastname'];

        $customerEmail = $customerData['email'];

        $customerName = $Firstname . ' ' . $Lastname;
//        $quote->reserveOrderId()->save();

        $incrementId = $quote->getReservedOrderId();
//        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
        $merchant_ReferenceID = $incrementId;

        $returnURL = str_replace('?___SID=U', '', $objectManager->create('Magento\Framework\UrlInterface')->getUrl('myfatoorah/checkout/apiresponse?apicid='. base64_encode($cartId), array('_secure' => true)));

        $currencyCode = $objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrency()->getCode();

        $this->token = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah_gateway/api_key');

        $istest = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/myfatoorah_gateway/is_testing');
        $gatewayUrl = 'https://api.myfatoorah.com';
        if ($istest) {
            $gatewayUrl = 'https://apitest.myfatoorah.com';
        }
        $currencyRate = (double) $objectManager->create('Magento\Store\Model\StoreManagerInterface')->getStore()->getCurrentCurrencyRate();

        $items = $quote->getAllVisibleItems();
        $invoiceItemsArr = array();
        $amount = 0;
        foreach ($items as $item) {
            $product_name = $item->getName();
            $itemPrice = $item->getPrice() * $currencyRate;
            $qty = $item->getQty();
            $amount = $amount + $qty * $itemPrice;
            $invoiceItemsArr[] = array('ItemName' => $product_name, 'Quantity' => $qty, 'UnitPrice' => $itemPrice);
        }

        $shipping = $quote->getShippingAddress()->getShippingAmount();
        if ($shipping != '0') {
            $amount = $amount + $shipping;

            $invoiceItemsArr[] = array('ItemName' => 'Shipping Amount', 'Quantity' => 1, 'UnitPrice' => $shipping);
        }
        $discount = $quote->getShippingAddress()->getDiscountAmount();
        if ($discount != '0') {
            $amount = $amount + $discount;

            $invoiceItemsArr[] = array('ItemName' => 'Discount Amount', 'Quantity' => 1, 'UnitPrice' => $discount);
        }
        $tax = $quote->getShippingAddress()->getTaxAmount();
        if ($tax != '0') {
            $amount = $amount + $tax;

            $invoiceItemsArr[] = array('ItemName' => 'Tax Amount', 'Quantity' => 1, 'UnitPrice' => $tax);
        }


        $getLocale = $objectManager->get('Magento\Framework\Locale\Resolver');
        $haystack = $getLocale->getLocale();
        $lang = strstr($haystack, '_', true);
        $data = array(
            'x_currency' => $currencyCode,
            'x_url_complete' => $returnURL,
            'x_url_cancel' => $returnURL,
            'x_lang' => $lang,
            'x_reference' => $merchant_ReferenceID,
            'x_invoice' => $merchant_ReferenceID,
            'x_amount' => $amount,
            'x_customer_name' => $customerName,
            'x_customer_email' => $customerEmail,
            'x_customer_phone' => $this->checkTelephone($addressArray['telephone']),
            'x_customer_phoneCode' => $this->getPhoneCode($addressArray['telephone']),
            'x_customer_billing_address1' => 'address',
            'x_customer_billing_address2' => 'address',
            'x_customer_billing_city' => $addressArray['city'],
            'x_customer_billing_state' => $addressArray['region'],
            'x_customer_billing_zip' => $addressArray['postcode'],
            'token' => $this->token,
            'items' => json_encode($invoiceItemsArr)
        );


        if ($gateway == 'myfatoorah') {
            $return = $this->sendPayment($gatewayUrl, $data);
        } else {
            $return = $this->initiatePayment($gatewayUrl, $data, $gateway);
        }
        if (isset($return['InvoiceURL'])) {
            header('Location:  ' . $return['InvoiceURL']);
            exit();
        } else {
            $this->log->info("Error ----- Order# " . $merchant_ReferenceID . ' Error Message : ' . $return['error']);
            return '{"error": {"param": " Order #' . $merchant_ReferenceID . '","message": "' . $return['error'] . '"}}';
        }
        
    }

    function checkTelephone($phone) {
        $code = array('+973', '+965', '+968', '+974', '+962', '+966', '+971', '00973', '00965', '00968', '00974', '00962', '00966', '00971', '973', '965', '968', '974', '962', '966', '971');
        $result = trim($phone);
        foreach ($code as $value) {
            if (strpos($phone, $value) !== false) {
                $result = str_replace($value, '', trim($phone));
                break;
            }
        }
        return $result;
    }

    public function initiatePayment($checkoutUrl, $data, $gateway) {
        // initiate payment & execute payment
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$checkoutUrl/v2/InitiatePayment",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"InvoiceAmount\": " . $data['x_amount'] . ",\"CurrencyIso\": \"" . $data['x_currency'] . "\"}",
            CURLOPT_HTTPHEADER => array("Authorization: Bearer " . $data['token'], "Content-Type: application/json"),
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $err_message = '';

        if ($err) {
            $err_message = "Initiate payment - cURL Error #:" . $err;
            $this->log->info('Inititate Payment Result -----  Order# ' . $data['x_reference'] . ' -- Error --' . $err_message);

            return array('Success' => false, 'error' => $err_message);
        } else {
            $json = json_decode((string) $response, true);
            $this->log->info('Inititate Payment Result -----  Order# ' . $data['x_reference'] . ' -- Response --' . $response);
//            print_r($response);
//            die;
            if (!isset($json["IsSuccess"]) || $json["IsSuccess"] == null || $json["IsSuccess"] == false) {
                if (isset($json['Message']) && !isset($json['ValidationErrors'])) {
                    $err_message = $json['Message'];
                    return array('Success' => false, 'error' => $err_message);
                } elseif (isset($json['ValidationErrors'])) {
                    foreach ($json['ValidationErrors'] as $value) {
                        $err_message .= $value['Error'];
                    }
                    return array('Success' => false, 'error' => $err_message);
                } else {
                    $err_message = $json['Data']['ErrorMessage'];
                    return array('Success' => false, 'error' => $err_message);
                }
                $this->log->info('Inititate Payment Result -----  Order# ' . $data['x_reference'] . ' -- Error --' . $err_message);
            } else {
                $PaymentMethodId = null;
                foreach ($json['Data']['PaymentMethods'] as $value) {
                    if ($value['PaymentMethodCode'] == $gateway) {
                        $PaymentMethodId = $value['PaymentMethodId'];
                    }
                }

                if($PaymentMethodId == null){
                    return $this->sendPayment($checkoutUrl, $data);
                }
                $jsonReq = '{"PaymentMethodId" :"' . $PaymentMethodId . '",'
                        . '"CustomerName": "' . $data['x_customer_name'].'",'
                        . '"DisplayCurrencyIso": "' . $data['x_currency'] . '",'
                        . '"MobileCountryCode":"' . $data['x_customer_phoneCode'] . '",'
                        . '"CustomerMobile": "' . $data['x_customer_phone'] . '",'
                        . '"CustomerEmail": "' . $data['x_customer_email'] . '",'
                        . '"InvoiceValue": ' . $data['x_amount'] . ','
                        . '"CallBackUrl": "' . $data['x_url_complete'] . '",'
                        . '"ErrorUrl": "' . $data['x_url_cancel'] . '",'
                        . '"Language": "' . $data['x_lang'] . '",'
                        . '"CustomerReference" :"' . $data['x_reference'] . '",'
                        . '"CustomerCivilId":"' . $data['x_reference'] . '",'
                        . '"UserDefinedField": "Custom field",'
                        . '"ExpireDate": "",'
                        . '"CustomerAddress" :'
                        . '{'
                        . '"Block":"",'
                        . '"Street":"' . $data['x_customer_billing_address1'] . ' ' . $data['x_customer_billing_address2'] . '",'
                        . '"HouseBuildingNo":"",'
                        . '"Address":"' . $data['x_customer_billing_city'] . ', ' . $data['x_customer_billing_state'] . ', ' . $data['x_customer_billing_zip'] . '",'
                        . '"AddressInstructions":""'
                        . '},'
                        . '"InvoiceItems":' . $data['items'] . '}';
                $this->log->info(" Execute Payment object ----- Order# " . $data['x_reference'] . ' Request Obj : ' . json_encode($jsonReq));
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "$checkoutUrl/v2/ExecutePayment",
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $jsonReq,
                    CURLOPT_HTTPHEADER => array("Authorization: Bearer " . $data['token'], "Content-Type: application/json"),
                ));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
//                    print_r($jsonReq);
//                    die;
                if ($err) {
                    $error_message = "Execute payment - cURL Error #:" . $err;
                    $this->log->info('Execute Payment Result -----  Order# ' . $data['x_reference'] . ' -- Error --' . $err_message);

                    return array('Success' => false, 'error' => $err_message);
                } else {
                    $json = json_decode((string) $response, true);
                    $this->log->info('Execute Payment Result -----  Order# ' . $data['x_reference'] . ' -- Response --' . $response);

                    if (!isset($json["Data"]["PaymentURL"])) {
                        $error_message = $json["Message"];
                        $this->log->info('Execute Payment Result -----  Order# ' . $data['x_reference'] . ' -- Error --' . $err_message);
                        return array('Success' => false, 'error' => $err_message);
                    } else {
                        $this->log->info("Execute Payment ----- Order# " . $data['x_reference'] . ' -- Payment URL : ' . $json['Data']['PaymentURL']);
                        return array('Success' => true, 'InvoiceURL' => $json["Data"]["PaymentURL"]);
                    }
                }
            }
        }
    }

    public function sendPayment($checkoutUrl, $data) {
        $jsonReq = '{"NotificationOption" :"Lnk",'
                . '"CustomerName": "' . $data['x_customer_name'] . '",'
                . '"DisplayCurrencyIso": "' . $data['x_currency'] . '",'
                . '"MobileCountryCode":"' . $data['x_customer_phoneCode'] . '",'
                . '"CustomerMobile": "' . $data['x_customer_phone'] . '",'
                . '"CustomerEmail": "' . $data['x_customer_email'] . '",'
                . '"InvoiceValue": ' . $data['x_amount'] . ','
                . '"CallBackUrl": "' . $data['x_url_complete'] . '",'
                . '"ErrorUrl": "' . $data['x_url_cancel'] . '",'
                . '"Language": "' . $data['x_lang'] . '",'
                . '"CustomerReference" :"' . $data['x_reference'] . '",'
                . '"CustomerCivilId":"' . $data['x_reference'] . '",'
                . '"UserDefinedField": "Custom field",'
                . '"ExpireDate": "",'
                . '"CustomerAddress" :'
                . '{'
                . '"Block":"",'
                . '"Street":"' . $data['x_customer_billing_address1'] . ' ' . $data['x_customer_billing_address2'] . '",'
                . '"HouseBuildingNo":"",'
                . '"Address":"' . $data['x_customer_billing_city'] . ', ' . $data['x_customer_billing_state'] . ', ' . $data['x_customer_billing_zip'] . '",'
                . '"AddressInstructions":""'
                . '},'
                . '"InvoiceItems":' . $data['items'] . '}';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$checkoutUrl/v2/SendPayment",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonReq,
            CURLOPT_HTTPHEADER => array("Authorization: Bearer " . $data['token'], "Content-Type: application/json"),
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $this->log->info(" Send Payment ----- Order# " . $data['x_reference'] . ' Response : ' . ($response));

        curl_close($curl);
        $err_message = '';

        if ($err) {
            $err_message = "cURL Error #:" . $err;
            return array('Success' => false, 'error' => $err_message);
        } else {
            $json = json_decode((string) $response, true);
            if (!isset($json["IsSuccess"]) || $json["IsSuccess"] == null || $json["IsSuccess"] == false) {
                if (isset($json['Message']) && !isset($json['ValidationErrors'])) {
                    $err_message = $json['Message'];
                    return array('Success' => false, 'error' => $err_message);
                } elseif (isset($json['ValidationErrors'])) {
                    foreach ($json['ValidationErrors'] as $value) {
                        $err_message .= $value['Error'];
                    }
                    return array('Success' => false, 'error' => $err_message);
                } else {
                    $err_message = $json['Data']['ErrorMessage'];
                    return array('Success' => false, 'error' => $err_message);
                }
            } else {
                return array('Success' => true, 'InvoiceURL' => $json['Data']['InvoiceURL']);
            }
        }
    }

    public function getPhoneCode($phone) {
        $code = array('+973', '+965', '+968', '+974', '+962', '+966', '+971', '00973', '00965', '00968', '00974', '00962', '00966', '00971', '973', '965', '968', '974', '962', '966', '971');
        $phone = trim($phone);
        $phoneCode = ' +965';
        foreach ($code as $value) {
            if (strpos($phone, $value) !== false) {
                $phoneCode = $value;
                break;
            }
        }
        return $phoneCode;
    }

}
