import {maybeShowButton} from "./maybeShowApplePayButton";

(
    function ({_, molliepaypalbutton, jQuery}) {

        if (_.isEmpty(molliepaypalbutton)) {
            return
        }

        const {product: {id, needShipping = true, isVariation = false, price, minFee, stock}, ajaxUrl} = molliepaypalbutton

        if (!id || !price || !ajaxUrl) {
            return
        }
        function getKeyByValue(object, value) {
            return Object.keys(object).find(key => object[key] === value);
        }
        const payPalButton = document.querySelector('#mollie-PayPal-button');
        const buttonParentNode = payPalButton.parentNode;
        let positionKey = false;
        if (buttonParentNode.hasChildNodes()) {
            positionKey = getKeyByValue(buttonParentNode.children, payPalButton);
        }

        const maybeShowButton = (underRange) => {
            if(underRange){
                hideButton()
            }else{
                showButton()
            }
        }
        const checkPriceRange = (productQuantity) => {
            let updatedPrice = productQuantity * price
            jQuery.ajax({
                url: ajaxUrl,
                method: 'POST',
                data: {
                    action: 'mollie_paypal_update_amount',
                    productId: productId,
                    productQuantity: productQuantity,
                    nonce: nonce,
                },
                success: (response) => {
                    updatedPrice = parseFloat(response.data)
                    const underRange = parseFloat(minFee) > updatedPrice
                    maybeShowButton(underRange)
                },
                error: (response) => {
                    console.warn(response)
                },
            })
        }
        const hideButton = () => {
            if(buttonParentNode !== null){
                buttonParentNode.removeChild(payPalButton)
            }
        }
        const showButton = () => {
            //if the node has a list of children, we need to insert the button at the correct position
            let sibling = buttonParentNode.children[positionKey]
            buttonParentNode.insertBefore(payPalButton, sibling)
        }
        let outOfStock = stock === 'outofstock'
        if (outOfStock) {
            hideButton()
            return
        }
        const nonce = payPalButton.children[0].value
        let productId = id
        let productQuantity = 1
        let redirectionUrl = ''
        document.querySelector('input.qty').addEventListener('change', event => {
            productQuantity = event.currentTarget.value
            checkPriceRange(productQuantity)
        })
        checkPriceRange(productQuantity)

        const fadeButton = () => {
            payPalButton.disabled = true;
            payPalButton.classList.add("buttonDisabled");
        }

        if (isVariation) {
            jQuery('.single_variation_wrap').on('show_variation', function (event, variation) {
                productId = ''
                fadeButton();
                // Fired when the user selects all the required dropdowns / attributes
                // and a final variation is selected / shown
                if (variation.is_virtual && variation.is_in_stock && variation.variation_id) {
                    productId = variation.variation_id
                    payPalButton.disabled = false;
                    payPalButton.classList.remove("buttonDisabled");
                }
            });
            jQuery('.reset_variations').on('click.wc-variation-form', function (event) {
                productId = ''
                fadeButton();

            });
            fadeButton();
        }
        if(payPalButton.parentNode == null){
            return
        }
        let preventSpam = false
        payPalButton.addEventListener('click', (evt) => {
            evt.preventDefault();
            if(!(payPalButton.parentNode !== null) || payPalButton.disabled){
                return
            }
            payPalButton.disabled = true;
            payPalButton.classList.add("buttonDisabled");
            if(!preventSpam){
                jQuery.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'mollie_paypal_create_order',
                        productId: productId,
                        productQuantity: productQuantity,
                        needShipping: needShipping,
                        'mollie-payments-for-woocommerce_issuer_paypal_button': 'paypal',
                        nonce: nonce,
                    },
                    success: (response) => {
                        let result = response.data

                        if (response.success === true) {
                            redirectionUrl = result['redirect'];
                            window.location.href = redirectionUrl
                        } else {
                            console.log(response.data)
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        payPalButton.disabled = false;
                        payPalButton.classList.remove("buttonDisabled");
                        console.warn(textStatus, errorThrown)
                    },
                })
            }
            preventSpam = true
            if(preventSpam){
                setTimeout(function() {
                    payPalButton.disabled = false;
                    payPalButton.classList.remove("buttonDisabled");
                    preventSpam = false
                }, 3000);
            }
        })
    }
)
(
    window
)



