<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Gateway\Config;

/**
 * Class Config.
 * Values returned from Magento\Payment\Gateway\Config\Config.getValue()
 * are taken by default from ScopeInterface::SCOPE_STORE
 */
class Config extends \Magento\Payment\Gateway\Config\Config {

    const CODE           = 'myfatoorah_gateway';
    const PLUGIN_VERSION = '2.0';
    const KEY_ACTIVE     = 'active';
    const KEY_API_KEY    = 'api_key';
    const KEY_GATEWAYS   = 'payment_gateway';
    const KEY_Title      = 'title';
    const KEY_DEBUG      = 'debug';
    
//    const KEY_SPECIFIC_COUNTRY = 'specificcountry';
    const KEY_MYFATOORAH_APPROVED_ORDER_STATUS = 'myfatoorah_approved_order_status';
    
    const KEY_EMAIL_CUSTOMER      = 'email_customer';
    const KEY_AUTOMATIC_INVOICE   = 'automatic_invoice';
    const KEY_IS_TESTING          = 'is_testing';
    const KEY_LAUNCH_TIME         = 'launch_time';
    const KEY_LAUNCH_TIME_UPDATED = 'launch_time_updated';

    /**
     * Get Launch Time
     *
     * @return string
     */
    public function getLaunchTime() {
        return $this->getValue(self::KEY_LAUNCH_TIME);
    }

    /**
     * Get Launch Time Updated
     *
     * @return string
     */
    public function getLaunchTimeUpdated() {
        return $this->getValue(self::KEY_LAUNCH_TIME_UPDATED);
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle() {
        return $this->getValue(self::KEY_Title);
    }

    /**
     * Get Logo
     *
     * @return string
     */
    public function getLogo() {
        $gateways = array_flip(explode(',', $this->getPaymentGateways()));
        $logo     = 'myfatoorah.png';
        if (count($gateways) == 1) {
            $logo = array_key_first($gateways) . '.png';
            }
        return $logo;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription() {
        return '';
    }

    /**
     * Get Gateway URL
     *
     * @return string
     */
    public function getGatewayUrl() {
        return 'https://' . ( $this->isTesting() ? 'apitest.myfatoorah.com' : 'api.myfatoorah.com' );
    }

    /**
     * get the myfatoorah refund gateway Url
     * @return string
     */
    public function getRefundUrl() {
        return 'https://' . ( $this->isTesting() ? 'apitest.myfatoorah.com/v2/MakeRefund' : 'api.myfatoorah.com/v2/MakeRefund' );
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getApiKey() {
        return $this->getValue(self::KEY_API_KEY);
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getMerchantNumber() {
        return ''; /////////???????
    }

    /**
     * Get MyFatoorah Approved Order Status
     *
     * @return string
     */
    public function getMyFatoorahApprovedOrderStatus() {
        return $this->getValue(self::KEY_MYFATOORAH_APPROVED_ORDER_STATUS);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isEmailCustomer() {
        return (bool) $this->getValue(self::KEY_EMAIL_CUSTOMER);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isAutomaticInvoice() {
        return (bool) $this->getValue(self::KEY_AUTOMATIC_INVOICE);
    }

    /**
     * Get Payment configuration status
     * @return bool
     */
    public function isActive() {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Get specific country
     *
     * @return string
     */
//    public function getSpecificCountry() {
//        return $this->getValue(self::KEY_SPECIFIC_COUNTRY);
//    }

    /**
     * Get if doing test transactions (request send to sandbox gateway)
     *
     * @return boolean
     */
    public function isTesting() {
        return (bool) $this->getValue(self::KEY_IS_TESTING);
    }

    /**
     * Get the version number of this plugin itself
     *
     * @return string
     */
    public function getVersion() {
        return self::PLUGIN_VERSION;
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getPaymentGateways() {
        return $this->getValue(self::KEY_GATEWAYS);
    }

}
