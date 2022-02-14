/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
        [
            'jquery',
            'Magento_Checkout/js/view/payment/default',
            'Magento_Checkout/js/model/url-builder',
            'mage/url',
            'Magento_Checkout/js/model/quote',
        ],
        function (
                $,
                Component,
                urlBuilder,
                url,
                quote) {
            'use strict';

            var self;

            return Component.extend({
                redirectAfterPlaceOrder: false,

                defaults: {
                    template: 'MyFatoorah_MyFatoorahPaymentGateway/payment/form'
                },

                initialize: function () {
                    this._super();
                    self = this;
                },
                initObservable: function () {



                    this._super()

                            .observe([

                                'transactionResult',
                                'gateways'

                            ]);

                    return this;

                },
                getCode: function () {
                    return 'myfatoorah_gateway';
                },

                getData: function () {
                    return {
                        'method': this.item.method,

                        'additional_data': {

                            'transaction_result': this.transactionResult(),
                            'gateways': this.gateways(),

                        }
                    };
                },

                afterPlaceOrder: function () {
                    window.location.replace(url.build('myfatoorah/checkout/index?gateway=' + jQuery("input[name=mf_payment]:checked").val()));
                },
                validate: function () {
                    return true;
                },

                getTitle: function () {
                    return window.checkoutConfig.payment.myfatoorah_gateway.title;
                },

                getDescription: function () {
                    return window.checkoutConfig.payment.myfatoorah_gateway.description;
                },

                getMyFatoorahLogo: function () {
                    var logo = window.checkoutConfig.payment.myfatoorah_gateway.logo;

                    return logo;
                },

                getAllowedCountries: function () {
                    return window.checkoutConfig.payment.myfatoorah_gateway.allowed_countries;
                },
                getGateways: function () {
                    var mf_gateways = window.checkoutConfig.payment.myfatoorah_gateway.gateways;

                    var gatewaysArr = _.map(mf_gateways, function (value, key) {

                        if (key === 'myfatoorah') {
                            var url = 'https://portal.myfatoorah.com/imgs/logo-myfatoorah-sm-blue.png';
                        } else {
                            var url = 'https://portal.myfatoorah.com/imgs/payment-methods/' + key + '.png';
                        }

                        jQuery('#mf_payment').append('<div class="mf-div"><input type="radio" name="mf_payment" id="myfatoorah_' + key + '" class="radio mf-radio" value="' + key + '"/><label for="myfatoorah_' + key + '"><img src="' + url + '"  class="mf-img" alt="' + value + '"/></label></div>');


                        gatewaysArr = {

                            'value': key,

                            'gateways': value

                        }
                        jQuery("input[name=mf_payment]:first").attr('checked', true);

                    });
                    if (jQuery("input[name=mf_payment]").length == 1) {
                        jQuery("input[name=mf_payment]").parent().hide();
                    }
                    return gatewaysArr;

                }

            });
        }
);
