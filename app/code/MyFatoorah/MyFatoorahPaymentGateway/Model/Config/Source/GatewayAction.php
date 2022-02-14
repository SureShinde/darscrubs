<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Model\Config\Source;
/**

 * Class GatewayAction

 */

class GatewayAction implements \Magento\Framework\Option\ArrayInterface

{

    /**

     * {@inheritdoc}

     */

    public function toOptionArray()

    {
        return array(
            array('value' => 'myfatoorah', 'label' =>'MyFatoorah'),
            array('value' => 'kn', 'label' =>'Knet'),
            array('value' => 'md', 'label' => 'Mada KSA'),
            array('value' => 'vm', 'label' => 'Visa / Master'),
            array('value' => 'b', 'label' => 'Benefit'),
            array('value' => 'np', 'label' => 'Qatar Debit Card - NAPS'),
            array('value' => 'uaecc', 'label' => 'Debit Cards UAE - VISA UAE'),
            array('value' => 's', 'label' => 'Sadad'),
            array('value' => 'ae', 'label' => 'AMEX'),
            array('value' => 'af', 'label' => 'AFS'),
            array('value' => 'ap', 'label' => 'Apple Pay'),
            array('value' => 'kf', 'label' => 'KFast'),
            
        );

    }

/**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'myfatoorah' => 'MyFatoorah',
            'kn' => 'Knet',
            'md' => 'Mada KSA',
            'vm' => 'Visa / Master',
            'b' => 'Benefit',
            'np' => 'Qatar Debit Card - NAPS',
            'uaecc' => 'Debit Cards UAE - VISA UAE',
            's' => 'Sadad',
            'ae' => 'AMEX',
            'af' => 'AFS',
            'ap' => 'Apple Pay',
            'kf' => 'KFast',
        );
    }
  

}
