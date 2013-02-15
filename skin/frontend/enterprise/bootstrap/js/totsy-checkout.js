var checkoutPayment = {};
jQuery(document).ready(function() {
    var billAddySelect = jQuery("#billing-address-select");
    var shipAddySelect = jQuery("#shipping-address-select");
    var newCardWrap = jQuery('.cc_info');
    //var newCardCtrls = jQuery( 'input, select', '.use-new-card-wrapper' );
    var billingAddress = jQuery('.totsy-selective-input-box', '#hpcheckout-billing-wrapper');
    var billFormInputs = jQuery('#hpcheckout-billing-form :input');
    //a namespace for operations toggling the 2 views of the payment section
    checkoutPayment = (function() {
        var hasProfile = '';
        var isCollapsed = '';
        var lastUsedAddressId = '';
        return {
            hasProfile: '',
            isCollapsed: false,
            lastUsedAddressId: '',
            toggleViews: function() {
                if (this.hasProfile) {
                    jQuery(".cc_save_card").appendTo(jQuery("#add_payment_save_card"));
                    var savedCardStyles = {
                        'width': '60px',
                        'margin-left': '10px',
                        'float': 'left'
                    };
                    jQuery('#billing-address-select').attr('disabled', true);
                    jQuery(".cc_save_card").css(savedCardStyles);
                    jQuery("#hpcheckout-payment-add-title").show();
                    jQuery("#use-card-method").show();
                    jQuery("#paymentfactory_tokenize_cc_save").contents("");
                    jQuery(".use-new-card-wrapper").show();
                    jQuery("#cc_save_text").html("Save");
                    jQuery(".use-new-card-wrapper").appendTo(jQuery("#add_cc_types"));
                    newCardWrap.hide();
                } else {
                    jQuery("[id='add_payment']").hide();
                    jQuery(".checkout-reward").css("padding-top", "0px");
                    jQuery(".use-new-card-wrapper").show();
                    jQuery("#use-card-method").hide();
                    jQuery("#hpcheckout-payment-add-title").hide();
                    jQuery("#cc_data").show();
                }
            },
            disableAddress: function(stateFlag, formId) {
                jQuery('#' + formId + ' :input').each(function(i) {
                    if (this.id !== "button_ship_to") {
                        jQuery("[id='" + this.id + "']").attr('disabled', stateFlag);
                    }
                });
            },
            setPaymentUI: function(elem) {
                if (jQuery(elem).val() == '') {
                    if (jQuery('#paypal_payment').length > 0) {
                        jQuery('#paypal_payment').attr("checked", false);
                    }
                    if (jQuery("#payment_form_paypal_express").length > 0) {
                        jQuery("#payment_form_paypal_express").show();
                    }
                    jQuery("#cc_data").show();
                    newCardWrap.show();
                    billAddySelect.removeAttr('disabled');
                    //Enable Billing Inputs if credit card not selected
                    checkoutPayment.disableAddress(false, 'hpcheckout-billing-form');
                    billFormInputs.each(function(i) {
                        jQuery(elem).val('');
                    });
                } else {
                    if (jQuery("#payment_form_paypal_express").length > 0) {
                        jQuery("#payment_form_paypal_express").hide();
                    }
                    jQuery('#billing-address').show();
                    jQuery('#shipping-address').show();
                    jQuery('.addresses').width(445);
                    //billingAddress.attr("disabled", true);
                    newCardWrap.hide();
                    if (checkoutPayment.isCollapsed == false) {
                        billAddySelect.val(jQuery("#address_" + jQuery(elem).val()).val()).change();
                        //Block Billing Inputs if credit card selected
                        this.disableAddress(true, 'hpcheckout-billing-form');
                        billAddySelect.attr('disabled', true);
                    } else {
                        this.disableAddress(false, 'hpcheckout-billing-form');
                        billAddySelect.removeAttr('disabled');
                    }
                }
            },
            useSavedCard: function() {
                jQuery("#paymentfactory_tokenize_cc_type input").attr("checked", false);
                jQuery('#billing-address-select').attr('disabled', true);
            },
            setPaymentType: function(elem) {
                if (elem.id == "paypal_payment") {
                    newCardWrap.hide();
                } else {
                    jQuery("[id='payment[cybersource_subid]']").attr("checked", false);
                    jQuery('#billing-address-select').attr('disabled', false);
                    newCardWrap.show();
                }
            },
            getCityAndStateByZip: function(formId) {
                //register events to the right form by type. 'type' could be billing or shipping
                var addressFormType = '';
                var results = false;
                if (formId) {
                    addressFormType = formId;
                }
                if (addressFormType == "shipping") {
                    selectedAddress = shipAddySelect.val();
                } else {
                    selectedAddress = billAddySelect.val();
                }
                if (selectedAddress == "" || typeof selectedAddress == "undefined") {
                    //hide these. fields when the user selects "new address"
                    jQuery("#" + addressFormType + "_city_and_state").fadeOut();
                    jQuery("#" + addressFormType + "_zip_info_message").fadeIn();
                    jQuery("[id='" + addressFormType + ":postcode']").keyup(function() {
                        if (this.value.length >= 5) {
                            jQuery("#" + addressFormType + "_zip_info_message").fadeOut();
                            jQuery.ajax({
                                url: "/customer/zipCodeInfo/lookup",
                                dataType: "json",
                                type: "POST",
                                data: {
                                    zip: jQuery("[id='" + addressFormType + ":postcode']").val()
                                },
                                error: function(data) {
                                    jQuery("#" + addressFormType + "_city_and_state").show();
                                    //jQuery("#" + addressFormType + "_zip_info_message").hide();
                                },
                                success: function(response) {
                                    if (typeof response[0] !== "undefined") {
                                        currentCityState = response[0];
                                        results = true;
                                    } else {
                                        results = false;
                                    }
                                },
                                complete: function() {
                                    if (results==true) {
                                        jQuery("#" + addressFormType + "_zip_info_message").fadeOut();
                                        //jQuery("#" + addressFormType + "_city_and_state_spinner").hide();
                                        jQuery("#" + addressFormType + "_city_and_state").fadeIn();
                                        jQuery("[id='" + addressFormType + ":city']").val(currentCityState['city']);
                                        jQuery("[id='" + addressFormType + ":region_id']").val(currentCityState['region_id']);
                                    } else {
                                        currentCityState="";
                                        jQuery("#" + addressFormType + "_city_and_state").fadeOut();
                                        jQuery("#" + addressFormType + "_zip_info_message").fadeIn();
                                    }
                                }
                            });
                        }
                    });
                } else {
                    jQuery("#" + addressFormType + "_city_and_state").fadeIn();
                    jQuery("#" + addressFormType + "_zip_info_message").hide();
                }
            }
        };
    })();
});