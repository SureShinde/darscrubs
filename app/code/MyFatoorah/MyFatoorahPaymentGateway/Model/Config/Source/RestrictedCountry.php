<?php

/**
 * Country config field renderer
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Model\Config\Source;

use Magento\Directory\Model\Config\Source\Country;

class RestrictedCountry extends Country {
    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection
     */
    public function __construct( \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection ) {
        $countryCollection->addCountryIdFilter( array( 'AU', 'NZ' ) );

        parent::__construct( $countryCollection );
    }
}
