<?php

use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\WooCommerceTests\TestCase;

function wc_string_to_bool($string)
{
    return is_bool($string) ? $string : ('yes' === strtolower(
            $string
        ) || 1 === $string || 'true' === strtolower($string) || '1' === $string);
}

/**
 * Converts a bool to a 'yes' or 'no'.
 *
 * @param bool $bool String to convert.
 * @return string
 * @since 3.0.0
 */
function wc_bool_to_string($bool)
{
    if (!is_bool($bool)) {
        $bool = wc_string_to_bool($bool);
    }
    return true === $bool ? 'yes' : 'no';
}


class WooCommerce
{
    public $cart = null;
    public $session = null;
    public $customer = null;
    public $shipping = null;

    public function api_request_url()
    {
    }


}

class WC_Customer
{
    public function set_shipping_country()
    {
    }

    public function set_billing_country()
    {
    }

    public function set_shipping_postcode()
    {
    }

    public function set_shipping_city()
    {
    }

    public function get_shipping_country()
    {
    }

    public function get_billing_country()
    {
    }

}

class WC_Shipping
{
    public function get_packages()
    {
    }

    public function calculate_shipping()
    {
    }


}

class WC_Shipping_Rate
{
    public function get_id()
    {
    }

    public function get_label()
    {
    }

    public function get_cost()
    {
    }

}

class WC_Payment_Gateway extends WC_Settings_API
{
    public $id;
    public $enabled;

    public function __construct($id = 1, $enabled = 'yes')
    {
        $this->id = $id;
        $this->enabled = $enabled;
    }

    public function init_settings()
    {
    }
}

class WC_Settings_API
{
    public function get_option()
    {
    }

    public function process_admin_options()
    {
    }

}

class Mollie_WC_Helper_Data
{
    public function getWcOrder()
    {
    }

    public function getOrderStatus()
    {
    }

    public function isWcSubscription($order)
    {
        if ($order == 'subs') {
            return true;
        }
        return false;
    }

    public function getUserMollieCustomerId()
    {
    }

    public function setUserMollieCustomerId()
    {
    }

    public function getOrderCurrency()
    {
        return 'EUR';
    }

    public function formatCurrencyValue($value)
    {
        return $value;
    }

    public function isSubscription()
    {
        return false;
    }
}

class Mollie_WC_Helper_Api
{
    public function getApiClient()
    {
        return new MollieApiClient();
    }
}

class MollieApiClient
{
    public $orders;

    public function __construct()
    {
        $this->orders = $this->mockApiEndpoint();
    }

    public function mockApiEndpoint()
    {
        $testCase = new TestCase();
        $mockBuilder = new PHPUnit_Framework_MockObject_MockBuilder($testCase, OrderEndpoint::class);
        $mock = $mockBuilder
            ->disableOriginalConstructor()
            ->setMethods(['get', 'create'])
            ->getMock();
        $mock->method('create')->willReturn(new MollieOrder());

        return $mock;
    }

}

class MollieOrder
{
    public $resource;
    public $id;
    public $mode;
    public $method;
    public $metadata;

    /**
     * MollieOrder constructor.
     * @param $resource
     */
    public function __construct($resource = 'order')
    {
        $this->resource = $resource;
        $this->id = 'mollieOrderId';
        $this->mode = 'test';
        $this->method = 'ideal';
        $this->metadata = new stdClass();
        $this->metadata->order_id = $this->id;
    }

    public function getCheckoutUrl()
    {
        return 'https://www.mollie.com/payscreen/order/checkout/wvndyu';
    }

}

class WC_Order
{
    public function get_order_key()
    {
    }

    public function get_id()
    {
    }

    public function get_status()
    {
    }

    public function add_order_note($note)
    {
    }

    public function key_is_valid()
    {
    }

    public function add_product()
    {
    }

    public function calculate_totals()
    {
    }

    public function payment_complete()
    {
    }

    public function get_customer_id()
    {
    }

    public function get_total()
    {
    }

    public function get_user_id()
    {
    }

    public function get_items()
    {
    }

    public function get_billing_first_name()
    {
    }

    public function get_billing_last_name()
    {
    }

    public function get_billing_email()
    {
    }

    public function get_shipping_first_name()
    {
    }

    public function get_shipping_last_name()
    {
    }

    public function get_billing_address_1()
    {
    }

    public function get_billing_address_2()
    {
    }

    public function get_billing_postcode()
    {
    }

    public function get_billing_city()
    {
    }

    public function get_billing_state()
    {
    }

    public function get_billing_country()
    {
    }

    public function get_shipping_address_1()
    {
    }

    public function get_shipping_address_2()
    {
    }

    public function get_shipping_postcode()
    {
    }

    public function get_shipping_city()
    {
    }

    public function get_shipping_state()
    {
    }

    public function get_shipping_country()
    {
    }

    public function get_shipping_methods()
    {
    }

    public function get_order_number()
    {
    }

    public function update_meta_data()
    {
    }

    public function delete_meta_data()
    {
    }

    public function save()
    {
    }
}

class WC_Order_Item
{
    public function get_quantity()
    {
    }

    public function get_total()
    {
    }

    public function get_total_tax()
    {
    }

    public function get_meta()
    {
    }

    public function get_name()
    {
    }
}

class WC_Order_Item_Product extends ArrayObject
{
    protected $data = array();

    public function __construct()
    {
    }

    public function get_item_quantity()
    {
    }

    public function get_name()
    {
    }

    public function get_id()
    {
    }
}

class WC_Cart
{
    public function needs_shipping()
    {
    }

    public function get_subtotal()
    {
    }

    public function is_empty()
    {
    }

    public function get_shipping_total()
    {
    }

    public function add_to_cart()
    {
    }

    public function get_total_tax()
    {
    }

    public function get_total()
    {
    }

    public function remove_cart_item()
    {
    }

    public function calculate_shipping()
    {
    }

    public function calculate_fees()
    {
    }

    public function calculate_totals()
    {
    }

    public function get_cart_contents()
    {
    }


}

class WC_HTTPS
{
    public static function force_https_url($string)
    {
    }
}

define('DAY_IN_SECONDS', 24 * 60 * 60);

class WC_Product
{
    public function get_id()
    {
    }
    public function get_type()
    {
    }

    public function get_price()
    {
    }

    public function get_sku()
    {
    }

    public function needs_shipping()
    {
    }

    public function is_taxable()
    {
    }

}

class WC_Countries
{
    public function get_allowed_countries()
    {
    }

    public function get_shipping_countries()
    {
    }

}

class WC_Shipping_Zones
{
    public function get_zones()
    {
    }
}

class WC_Session
{
    public function set()
    {
    }
}
/**
 * This class is a partial mock to create an order
 * the order created is also partially mocked
 */
class Mollie_WC_Helper_PaymentFactory
{
    public function getPaymentObject($data)
    {
        $dataHelper = Mollie_WC_Plugin::getDataHelper();
        $refundLineItemsBuilder = new Mollie_WC_Payment_RefundLineItemsBuilder($dataHelper);
        $apiHelper = Mollie_WC_Plugin::getApiHelper();
        $settingsHelper = Mollie_WC_Plugin::getSettingsHelper();

        $orderItemsRefunded = new Mollie_WC_Payment_OrderItemsRefunder(
            $refundLineItemsBuilder,
            $dataHelper,
            $apiHelper->getApiClient($settingsHelper->isTestModeEnabled())->orders
        );
        return $this->pluginOrder($orderItemsRefunded,$data);
    }

    public function pluginOrder($orderItemsRefunded,$data)
    {
        $testCase = new TestCase();
        $mockBuilder = new PHPUnit_Framework_MockObject_MockBuilder($testCase, Mollie_WC_Payment_Order::class);
        $mock = $mockBuilder
            ->setConstructorArgs([$orderItemsRefunded, $data])
            ->setMethods(['createShippingAddress', 'createBillingAddress'])
            ->getMock();
        $mock->method('createShippingAddress')->willReturn('shippingAddressHere');
        $mock->method('createBillingAddress')->willReturn('billingAddressHere');
        return $mock;
    }
}

