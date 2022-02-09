<?php

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Mollie\WooCommerce\Gateway;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Mollie\WooCommerce\BlockService\CheckoutBlockService;
use Mollie\WooCommerce\Buttons\ApplePayButton\AppleAjaxRequests;
use Mollie\WooCommerce\Buttons\ApplePayButton\ApplePayDirectHandler;
use Mollie\WooCommerce\Buttons\ApplePayButton\ResponsesToApple;
use Mollie\WooCommerce\Buttons\PayPalButton\DataToPayPal;
use Mollie\WooCommerce\Buttons\PayPalButton\PayPalAjaxRequests;
use Mollie\WooCommerce\Buttons\PayPalButton\PayPalButtonHandler;
use Mollie\WooCommerce\Notice\AdminNotice;
use Mollie\WooCommerce\Notice\NoticeInterface;
use Mollie\WooCommerce\Payment\MollieObject;
use Mollie\WooCommerce\Payment\MollieOrderService;
use Mollie\WooCommerce\Payment\OrderInstructionsService;
use Mollie\WooCommerce\Payment\PaymentCheckoutRedirectService;
use Mollie\WooCommerce\Payment\PaymentFactory;
use Mollie\WooCommerce\Payment\PaymentFieldsService;
use Mollie\WooCommerce\Payment\PaymentService;
use Mollie\WooCommerce\PaymentMethods\Directdebit;
use Mollie\WooCommerce\PaymentMethods\IconFactory;
use Mollie\WooCommerce\SDK\Api;
use Mollie\WooCommerce\Settings\Settings;
use Mollie\WooCommerce\Subscription\MollieSepaRecurringGateway;
use Mollie\WooCommerce\Subscription\MollieSubscriptionGateway;
use Mollie\WooCommerce\Shared\GatewaySurchargeHandler;
use Mollie\WooCommerce\Gateway\Voucher\MaybeDisableGateway;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface as Logger;

class GatewayModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public const APPLE_PAY_METHOD_ALLOWED_KEY = 'mollie_apple_pay_method_allowed';
    public const POST_DATA_KEY = 'post_data';
    /**
     * @var mixed
     */
    protected $gatewayClassnames;
    /**
     * @var mixed
     */
    protected $pluginId;

    public function services(): array
    {
        return [
            'gateway.classnames' => static function (): array {
                return [
                    'Mollie_WC_Gateway_BankTransfer',
                    'Mollie_WC_Gateway_Belfius',
                    'Mollie_WC_Gateway_Creditcard',
                    'Mollie_WC_Gateway_DirectDebit',
                    'Mollie_WC_Gateway_EPS',
                    'Mollie_WC_Gateway_Giropay',
                    'Mollie_WC_Gateway_Ideal',
                    'Mollie_WC_Gateway_Kbc',
                    'Mollie_WC_Gateway_KlarnaPayLater',
                    'Mollie_WC_Gateway_KlarnaSliceIt',
                    'Mollie_WC_Gateway_KlarnaPayNow',
                    'Mollie_WC_Gateway_Bancontact',
                    'Mollie_WC_Gateway_PayPal',
                    'Mollie_WC_Gateway_Paysafecard',
                    'Mollie_WC_Gateway_Przelewy24',
                    'Mollie_WC_Gateway_Sofort',
                    'Mollie_WC_Gateway_Giftcard',
                    'Mollie_WC_Gateway_ApplePay',
                    'Mollie_WC_Gateway_MyBank',
                    'Mollie_WC_Gateway_Voucher',
                ];
            },
            'gateway.instances' => function (ContainerInterface $container): array {
                return $this->instantiatePaymentMethodGateways($container);
            },
            'gateway.paymentMethods' => static function (): array {
                return [
                    'Banktransfer',
                    'Belfius',
                    'Creditcard',
                    'DirectDebit',
                    'Eps',
                    'Giropay',
                    'Ideal',
                    'Kbc',
                    'Klarnapaylater',
                    'Klarnapaynow',
                    'Klarnasliceit',
                    'Bancontact',
                    'Paypal',
                    'Paysafecard',
                    'Przelewy24',
                    'Sofort',
                    'Giftcard',
                    'Applepay',
                    'Mybank',
                    'Voucher',
                ];
            },
            IconFactory::class => static function (ContainerInterface $container): IconFactory {
                $pluginUrl = $container->get('shared.plugin_url');
                $pluginPath = $container->get('shared.plugin_path');
                return new IconFactory($pluginUrl, $pluginPath);
            },
            PaymentService::class => static function (ContainerInterface $container): PaymentService {
                $logger = $container->get(Logger::class);
                $notice = $container->get(AdminNotice::class);
                $paymentFactory = $container->get(PaymentFactory::class);
                $data = $container->get('settings.data_helper');
                $api = $container->get('SDK.api_helper');
                $settings = $container->get('settings.settings_helper');
                $pluginId = $container->get('shared.plugin_id');
                $paymentCheckoutRedirectService = $container->get(PaymentCheckoutRedirectService::class);
                return new PaymentService($notice, $logger, $paymentFactory, $data, $api, $settings, $pluginId, $paymentCheckoutRedirectService);
            },
            OrderInstructionsService::class => static function (): OrderInstructionsService {
                return new OrderInstructionsService();
            },
            PaymentFieldsService::class => static function (ContainerInterface $container): PaymentFieldsService {
                $data = $container->get('settings.data_helper');
                return new PaymentFieldsService($data);
            },
            PaymentCheckoutRedirectService::class => static function (
                ContainerInterface $container
            ): PaymentCheckoutRedirectService {
                $data = $container->get('settings.data_helper');
                return new PaymentCheckoutRedirectService($data);
            },
            Surcharge::class => static function (ContainerInterface $container): Surcharge {
                return new Surcharge();
            },
            MollieOrderService::class => static function (ContainerInterface $container): MollieOrderService {
                $HttpResponseService = $container->get('SDK.HttpResponse');
                $logger = $container->get(Logger::class);
                $paymentFactory = $container->get(PaymentFactory::class);
                $data = $container->get('settings.data_helper');
                $pluginId = $container->get('shared.plugin_id');
                return new MollieOrderService($HttpResponseService, $logger, $paymentFactory, $data, $pluginId);
            },
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        $this->pluginId = $container->get('shared.plugin_id');
        $this->gatewayClassnames = $container->get('gateway.classnames');
        add_filter($this->pluginId . '_retrieve_payment_gateways', function () {
            return $this->gatewayClassnames;
        });

        add_filter('woocommerce_payment_gateways', function ($gateways) use ($container) {
            $mollieGateways = $container->get('gateway.instances');
            return array_merge($gateways, $mollieGateways);
        });
        add_filter('woocommerce_payment_gateways', [$this, 'maybeDisableApplePayGateway'], 20);
         add_filter('woocommerce_payment_gateways', static function ($gateways) {
            $maybeEnablegatewayHelper = new MaybeDisableGateway();

            return $maybeEnablegatewayHelper->maybeDisableMealVoucherGateway($gateways);
         });
        add_filter(
            'woocommerce_payment_gateways',
            [$this, 'maybeDisableBankTransferGateway'],
            20
        );
        // Disable SEPA as payment option in WooCommerce checkout
        add_filter(
            'woocommerce_available_payment_gateways',
            [$this, 'disableSEPAInCheckout'],
            11,
            1
        );

        // Disable Mollie methods on some pages
        add_filter(
            'woocommerce_available_payment_gateways',
            [$this, 'disableMollieOnPaymentMethodChange'],
            11,
            1
        );
        add_action(
            'woocommerce_after_order_object_save',
            static function () {
                $mollieWooCommerceSession = mollieWooCommerceSession();
                if ($mollieWooCommerceSession instanceof \WC_Session) {
                    $mollieWooCommerceSession->__unset(self::APPLE_PAY_METHOD_ALLOWED_KEY);
                }
            }
        );

        // Set order to paid and processed when eventually completed without Mollie
        add_action('woocommerce_payment_complete', [$this, 'setOrderPaidByOtherGateway'], 10, 1);
        $notice = $container->get(AdminNotice::class);
        $logger = $container->get(Logger::class);
        $pluginUrl = $container->get('shared.plugin_url');
        $apiHelper = $container->get('SDK.api_helper');
        $settingsHelper = $container->get('settings.settings_helper');
        $appleGateway = $container->get('gateway.instances')['mollie_wc_gateway_applepay'];
        $this->gatewaySurchargeHandling($container->get(Surcharge::class));
        $this->mollieApplePayDirectHandling($notice, $logger, $apiHelper, $settingsHelper, $appleGateway);
        $gatewayInstances = $container->get('gateway.instances');
        $paypalGateway = $gatewayInstances['mollie_wc_gateway_paypal'];
        $this->molliePayPalButtonHandling($paypalGateway, $notice, $logger, $pluginUrl);
        $checkoutBlockHandler = new CheckoutBlockService($container->get('settings.data_helper'));
        $checkoutBlockHandler->bootstrapAjaxRequest();
        add_action( 'woocommerce_blocks_checkout_update_order_meta', function($order) use($gatewayInstances){
            $orderPaymentMethod = $order->get_payment_method();
            $title = $orderPaymentMethod? $gatewayInstances[$orderPaymentMethod]->title: '';
            if(!$title){
                return $order;
            }
            $order->update_meta_data('_payment_method_title', $title);
            return $order;
        } );
        return true;
    }

    /**
     * Disable Bank Transfer Gateway
     *
     * @param array $gateways
     * @return array
     */
    public function maybeDisableBankTransferGateway(array $gateways): array
    {
        $isWcApiRequest = (bool)filter_input(INPUT_GET, 'wc-api', FILTER_SANITIZE_STRING);
        $bankTransferSettings = get_option('mollie_wc_gateway_banktransfer_settings', false);
        $isSettingActivated = false;
        if ($bankTransferSettings && isset($bankTransferSettings['order_dueDate'])) {
            $isSettingActivated = $bankTransferSettings['order_dueDate'] > 0;
        }

        /*
         * There is only one case where we want to filter the gateway and it's when the
         * pay-page render the available payments methods AND the setting is enabled
         *
         * For any other case we want to be sure bank transfer gateway is included.
         */
        if (
            $isWcApiRequest ||
            !$isSettingActivated ||
            is_checkout() && ! is_wc_endpoint_url('order-pay') ||
            !wp_doing_ajax() && ! is_wc_endpoint_url('order-pay') ||
            is_admin()
        ) {
            return $gateways;
        }
        $bankTransferGatewayClassName = 'mollie_wc_gateway_banktransfer';
        unset($gateways[$bankTransferGatewayClassName]);

        return  $gateways;
    }

    /**
     * Disable Apple Pay Gateway
     *
     * @param array $gateways
     * @return array
     */
    public function maybeDisableApplePayGateway(array $gateways): array
    {
        $isWcApiRequest = (bool)filter_input(INPUT_GET, 'wc-api', FILTER_SANITIZE_STRING);
        $wooCommerceSession = mollieWooCommerceSession();

        /*
         * There is only one case where we want to filter the gateway and it's when the checkout
         * page render the available payments methods.
         *
         * For any other case we want to be sure apple pay gateway is included.
         */
        if (
            $isWcApiRequest ||
            !$wooCommerceSession instanceof \WC_Session ||
            !doing_action('woocommerce_payment_gateways') ||
            !wp_doing_ajax() && ! is_wc_endpoint_url('order-pay') ||
            is_admin()
        ) {
            return $gateways;
        }

        if ($wooCommerceSession->get(self::APPLE_PAY_METHOD_ALLOWED_KEY, false)) {
            return $gateways;
        }

        $applePayGatewayClassName = 'mollie_wc_gateway_applepay';
        $postData = (string)filter_input(
            INPUT_POST,
            self::POST_DATA_KEY,
            FILTER_SANITIZE_STRING
        ) ?: '';
        parse_str($postData, $postData);

        $applePayAllowed = isset($postData[self::APPLE_PAY_METHOD_ALLOWED_KEY])
            && $postData[self::APPLE_PAY_METHOD_ALLOWED_KEY];

        if (!$applePayAllowed) {
            unset($gateways[$applePayGatewayClassName]);
        }

        if ($applePayAllowed) {
            $wooCommerceSession->set(self::APPLE_PAY_METHOD_ALLOWED_KEY, true);
        }

        return $gateways;
    }

    public function gatewaySurchargeHandling(Surcharge $surcharge)
    {
        new GatewaySurchargeHandler($surcharge);
    }

    /**
     * Don't show SEPA Direct Debit in WooCommerce Checkout
     */
    public function disableSEPAInCheckout($available_gateways)
    {
        if (is_checkout()) {
            unset($available_gateways['mollie_wc_gateway_directdebit']);
        }

        return $available_gateways;
    }

    /**
     * Don't show Mollie Payment Methods in WooCommerce Account > Subscriptions
     */
    public function disableMollieOnPaymentMethodChange($available_gateways)
    {
        // Can't use $wp->request or is_wc_endpoint_url()
        // to check if this code only runs on /subscriptions and /view-subscriptions,
        // because slugs/endpoints can be translated (with WPML) and other plugins.
        // So disabling on is_account_page (if not checkout, bug in WC) and $_GET['change_payment_method'] for now.

        // Only disable payment methods if WooCommerce Subscriptions is installed
        if (class_exists('WC_Subscription')) {
            // Do not disable if account page is also checkout
            // (workaround for bug in WC), do disable on change payment method page (param)
            if ((! is_checkout() && is_account_page()) || ! empty($_GET['change_payment_method'])) {
                foreach ($available_gateways as $key => $value) {
                    if (strpos($key, 'mollie_') !== false) {
                        unset($available_gateways[ $key ]);
                    }
                }
            }
        }

        return $available_gateways;
    }

    /**
     * If an order is paid with another payment method (gateway) after a first payment was
     * placed with Mollie, set a flag, so status updates (like expired) aren't processed by
     * Mollie Payments for WooCommerce.
     */
    public function setOrderPaidByOtherGateway($order_id)
    {
        $order = wc_get_order($order_id);

        $mollie_payment_id = $order->get_meta('_mollie_payment_id', $single = true);
        $order_payment_method = $order->get_payment_method();

        if ($mollie_payment_id !== '' && (strpos($order_payment_method, 'mollie') === false)) {
            $order->update_meta_data('_mollie_paid_by_other_gateway', '1');
            $order->save();
        }
        return true;
    }

    /**
     * Bootstrap the ApplePay button logic if feature enabled
     */
    public function mollieApplePayDirectHandling(NoticeInterface $notice, Logger $logger, Api $apiHelper, Settings $settingsHelper, MollieSubscriptionGateway $appleGateway)
    {
        $buttonEnabledCart = mollieWooCommerceIsApplePayDirectEnabled('cart');
        $buttonEnabledProduct = mollieWooCommerceIsApplePayDirectEnabled('product');

        if ($buttonEnabledCart || $buttonEnabledProduct) {
            $notices = new AdminNotice();
            $responseTemplates = new ResponsesToApple($logger, $appleGateway);
            $ajaxRequests = new AppleAjaxRequests($responseTemplates, $notice, $logger, $apiHelper, $settingsHelper);
            $applePayHandler = new ApplePayDirectHandler($notices, $ajaxRequests);
            $applePayHandler->bootstrap($buttonEnabledProduct, $buttonEnabledCart);
        }
    }

    /**
     * Bootstrap the Mollie_WC_Gateway_PayPal button logic if feature enabled
     */
    public function molliePayPalButtonHandling(
        $gateway,
        NoticeInterface $notice,
        Logger $logger,
        string $pluginUrl
    ) {

        $enabledInProduct = (mollieWooCommerceIsPayPalButtonEnabled('product'));
        $enabledInCart = (mollieWooCommerceIsPayPalButtonEnabled('cart'));
        $shouldBuildIt = $enabledInProduct || $enabledInCart;

        if ($shouldBuildIt) {
            $ajaxRequests = new PayPalAjaxRequests($gateway, $notice, $logger);
            $data = new DataToPayPal($pluginUrl);
            $payPalHandler = new PayPalButtonHandler($ajaxRequests, $data);
            $payPalHandler->bootstrap($enabledInProduct, $enabledInCart);
        }
    }

    public function instantiatePaymentMethodGateways(ContainerInterface $container): array
    {

        $logger = $container->get(Logger::class);
        $notice = $container->get(AdminNotice::class);
        $iconFactory = $container->get(IconFactory::class);
        $paymentService = $container->get(PaymentService::class);
        $surchargeService = $container->get(Surcharge::class);
        $mollieOrderService = $container->get(MollieOrderService::class);
        $HttpResponseService = $container->get('SDK.HttpResponse');
        $settingsHelper = $container->get('settings.settings_helper');
        $apiHelper = $container->get('SDK.api_helper');
        $paymentMethods = $container->get('gateway.paymentMethods');
        $data = $container->get('settings.data_helper');
        $orderInstructionsService = new OrderInstructionsService();
        $paymentFieldsService = $container->get(PaymentFieldsService::class);
        $mollieObject = $container->get(MollieObject::class);
        $paymentFactory = $container->get(PaymentFactory::class);
        $pluginId = $container->get('shared.plugin_id');
        $gateways = [];

        foreach ($paymentMethods as $paymentMethodName) {
            $paymentMethodName = 'Mollie\\WooCommerce\\PaymentMethods\\' . $paymentMethodName;
            $paymentMethod = new $paymentMethodName(
                $iconFactory,
                $settingsHelper,
                $paymentFieldsService,
                $surchargeService
            );
            $isSepa = $paymentMethod->getProperty('SEPA');
            $paymentMethodId = $paymentMethod->getProperty('id');
            $key = 'mollie_wc_gateway_' . $paymentMethodId;
            //si lo saco de aquí y devuelve solo uno gatewayFactory(type)
            if ($isSepa) {
                $directDebit = new Directdebit(
                    $iconFactory,
                    $settingsHelper,
                    $paymentFieldsService,
                    $surchargeService
                );
                $gateways[$key] = new MollieSepaRecurringGateway(
                    $directDebit,
                    $paymentMethod,
                    $paymentService,
                    $orderInstructionsService,
                    $mollieOrderService,
                    $data,
                    $logger,
                    $notice,
                    $HttpResponseService,
                    $settingsHelper,
                    $mollieObject,
                    $paymentFactory,
                    $pluginId,
                    $apiHelper
                );
            } elseif ($paymentMethod->getProperty('Subscription')) {
                $gateways[$key] = new MollieSubscriptionGateway(
                    $paymentMethod,
                    $paymentService,
                    $orderInstructionsService,
                    $mollieOrderService,
                    $data,
                    $logger,
                    $notice,
                    $HttpResponseService,
                    $settingsHelper,
                    $mollieObject,
                    $paymentFactory,
                    $pluginId,
                    $apiHelper
                );
            } else {
                $gateways[$key] = new MolliePaymentGateway(
                    $paymentMethod,
                    $paymentService,
                    $orderInstructionsService,
                    $mollieOrderService,
                    $data,
                    $logger,
                    $notice,
                    $HttpResponseService,
                    $mollieObject,
                    $paymentFactory,
                    $pluginId
                );
            }
        }
        return $gateways;
    }
}