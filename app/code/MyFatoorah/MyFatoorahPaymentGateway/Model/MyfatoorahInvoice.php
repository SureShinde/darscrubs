<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Model;

class MyfatoorahInvoice extends \Magento\Framework\Model\AbstractModel {

    public function _construct() {
        $this->_init('MyFatoorah\MyFatoorahPaymentGateway\Model\ResourceModel\MyfatoorahInvoice');
    }

}