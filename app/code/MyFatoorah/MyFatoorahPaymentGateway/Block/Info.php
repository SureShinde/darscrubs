<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use MyFatoorah\MyFatoorahPaymentGateway\Model\ResourceModel\MyfatoorahInvoice\CollectionFactory;

/**
 * Class Info
 */
class Info extends ConfigurableInfo {

    /**
     * @var string
     */
    protected $_template = 'info/default.phtml';
    protected $mfInvoiceFactory;
    private $cards       = [
        'myfatoorah' => 'MyFatoorah',
        'kn'         => 'KNET',
        'vm'         => 'VISA/MASTER',
        'md'         => 'MADA',
        'b'          => 'Benefit',
        'np'         => 'Qatar Debit Cards',
        'uaecc'      => 'UAE Debit Cards',
        's'          => 'Sadad',
        'ae'         => 'AMEX',
        'ap'         => 'Apple Pay',
        'kf'         => 'KFast',
        'af'         => 'AFS',
        'stc'        => 'STC Pay',
    ];

    public function __construct(
            CollectionFactory $mfInvoiceFactory,
            Context $context,
            ConfigInterface $config,
            array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->mfInvoiceFactory = $mfInvoiceFactory;
    }

    /**
     * Returns label
     *
     * @param string $field
     *
     * @return Phrase
     */
    protected function getLabel($field) {
        return __($field);
    }

    public function getSpecificInformation() {

        $item = $this->getInvoiceData();
        if ($item) {
            $data = [
                'Invoice ID'  => $item['invoice_id'],
                'Gateway'     => __($this->cards[$item['gateway_id']]),
                'Invoice URL' => $item['invoice_url'],
            ];

            if (isset($item['gateway_transaction_id'])) {
                $data['Transaction ID'] = $item['gateway_transaction_id'];
            }

            return $data;
        }
    }

    public function getMFInformation() {

        $item = $this->getInvoiceData();
        if ($item) {
            $data = [
                'Invoice ID' => '<a href="' . $item['invoice_url'] . '">' . $item['invoice_id'] . '</a>',
                'Gateway'    => __($this->cards[$item['gateway_id']]),
            ];

            if (isset($item['gateway_transaction_id'])) {
                $data['Transaction ID'] = $item['gateway_transaction_id'];
            }

            return $data;
        }
    }

    public function getInvoiceData() {

        $mfOrder = $this->getInfo()->getOrder();
        $orderId = $mfOrder->getRealOrderId();
        if (!$orderId) {
            return;
        }

        $collection = $this->mfInvoiceFactory->create()->addFieldToFilter('order_id', $orderId);
        $items      = $collection->getData();

        if (isset($items[0])) {
            return $items[0];
        }
    }

}
