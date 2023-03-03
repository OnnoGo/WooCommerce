import molliePaymentMethod from './blocks/molliePaymentMethod'

(
    function ({ mollieBlockData, wc, _, jQuery}) {
        if (_.isEmpty(mollieBlockData)) {
            return;
        }
        window.onload = (event) => {
            const { registerPaymentMethod } = wc.wcBlocksRegistry;
            const { ajaxUrl, filters, gatewayData, availableGateways } = mollieBlockData.gatewayData;
            const {useEffect} = wp.element;
            const isAppleSession = typeof window.ApplePaySession === "function"
            let shippingCompany = document.getElementById('shipping-company');
            let billingCompany = document.getElementById('billing-company');
            let companyField = shippingCompany ? shippingCompany : billingCompany;
            let isCompanyFieldVisible = companyField && companyField.style.display !== 'none';
            let companyNameString = companyField && companyField.parentNode.querySelector("label[for='" + companyField.id + "']").innerHTML;
            gatewayData.forEach(item => {
                let register = () => registerPaymentMethod(molliePaymentMethod(useEffect, ajaxUrl, filters, gatewayData, availableGateways, item, jQuery, companyNameString));
                if (item.name === 'mollie_wc_gateway_billie') {
                    if (isCompanyFieldVisible) {
                        register();
                    }
                    return;
                }
                if (item.name === 'mollie_wc_gateway_applepay' ) {
                    if (isAppleSession && window.ApplePaySession.canMakePayments()) {
                        register();
                    }
                    return;
                }
                register();
            });
        };

    }
)(window, wc)
