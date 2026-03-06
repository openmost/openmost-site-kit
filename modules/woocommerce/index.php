<?php
/**
 * WooCommerce Ecommerce Tracking Module
 *
 * Provides three tracking methods for WooCommerce:
 * 1. Server-side PHP tracking via Matomo API
 * 2. Client-side JavaScript tracking via Matomo JS tracker
 * 3. DataLayer push for GA4/GTM compatibility
 *
 * All methods can be enabled/disabled independently in settings.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only load if WooCommerce is active
add_action('plugins_loaded', 'omsk_woocommerce_init');

function omsk_woocommerce_init()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Initialize ecommerce tracking
    new OMSK_WooCommerce_Tracking();
}

class OMSK_WooCommerce_Tracking
{
    /**
     * @var array Plugin settings
     */
    private $options;

    /**
     * @var bool Enable server-side ecommerce tracking
     */
    private $enableServerTracking = false;

    /**
     * @var bool Enable JavaScript ecommerce tracking
     */
    private $enableJsTracking = false;

    /**
     * @var bool Enable dataLayer ecommerce push
     */
    private $enableDataLayer = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = get_option('omsk-settings', array());

        // Determine which tracking method is active and check corresponding ecommerce setting
        $enableServerTrackingMethod = !empty($this->options['omsk-matomo-enable-server-tracking-field']);
        $enableClassicTracking = !empty($this->options['omsk-matomo-enable-classic-tracking-code-field']);
        $enableMtmTracking = !empty($this->options['omsk-matomo-enable-mtm-tracking-code-field']);

        // Server-side PHP tracking -> Server-side ecommerce
        if ($enableServerTrackingMethod) {
            $this->enableServerTracking = !empty($this->options['omsk-matomo-enable-server-ecommerce-field']);
        }

        // Classic JS tracking -> JS ecommerce (_paq)
        if ($enableClassicTracking) {
            $this->enableJsTracking = !empty($this->options['omsk-matomo-enable-js-ecommerce-field']);
        }

        // Tag Manager -> dataLayer ecommerce
        if ($enableMtmTracking) {
            $this->enableDataLayer = !empty($this->options['omsk-matomo-enable-datalayer-ecommerce-field']);
        }

        if (!$this->enableServerTracking && !$this->enableJsTracking && !$this->enableDataLayer) {
            return;
        }

        // Product view
        add_action('woocommerce_after_single_product', array($this, 'trackProductView'));

        // Add to cart
        add_action('woocommerce_add_to_cart', array($this, 'trackAddToCart'), 10, 6);

        // Remove from cart
        add_action('woocommerce_remove_cart_item', array($this, 'trackRemoveFromCart'), 10, 2);

        // Cart update
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'trackCartUpdate'), 10, 4);

        // Checkout/Order completion
        add_action('woocommerce_thankyou', array($this, 'trackOrderComplete'), 10, 1);

        // Category view
        add_action('woocommerce_after_shop_loop', array($this, 'trackCategoryView'));

        // For classic JS tracking: inject setEcommerceView BEFORE trackPageView in wp_head
        // The main tracking code runs at priority 10, so we use priority 9
        if ($this->enableJsTracking) {
            add_action('wp_head', array($this, 'outputEcommerceViewScript'), 9);
        }

        // Enqueue scripts for JS tracking (cart updates, purchases) and dataLayer
        if ($this->enableJsTracking || $this->enableDataLayer) {
            add_action('wp_footer', array($this, 'outputTrackingScripts'), 99);
        }
    }

    /**
     * Get Matomo tracker instance for server-side tracking
     *
     * @return MatomoTracker|null
     */
    private function getTracker()
    {
        if (!$this->enableServerTracking) {
            return null;
        }

        $host = isset($this->options['omsk-matomo-host-field']) ? $this->options['omsk-matomo-host-field'] : '';
        $idSite = isset($this->options['omsk-matomo-idsite-field']) ? $this->options['omsk-matomo-idsite-field'] : '';
        $tokenAuth = isset($this->options['omsk-matomo-token-auth-field']) ? $this->options['omsk-matomo-token-auth-field'] : '';

        if (!$host || !$idSite) {
            return null;
        }

        // Check user exclusion
        $excludedRoles = isset($this->options['omsk-matomo-excluded-roles-field']) ? (array) $this->options['omsk-matomo-excluded-roles-field'] : array();
        if (function_exists('omsk_should_exclude_user') && omsk_should_exclude_user($excludedRoles)) {
            return null;
        }

        $tracker = new MatomoTracker((int) $idSite, $host);
        $enableUserIdTracking = !empty($this->options['omsk-matomo-enable-userid-tracking-field']);

        // Configure common visitor attributes (visitor ID, IP, UA, referrer, User ID).
        if (function_exists('omsk_configure_tracker')) {
            omsk_configure_tracker($tracker, $tokenAuth, $enableUserIdTracking);
        } else {
            if ($tokenAuth) {
                $tracker->setTokenAuth($tokenAuth);
            }
            $tracker->disableCookieSupport();
        }

        if (function_exists('omsk_get_current_url')) {
            $tracker->setUrl(omsk_get_current_url());
        }

        return $tracker;
    }

    /**
     * Get product data for tracking
     *
     * @param WC_Product $product
     * @return array
     */
    private function getProductData($product)
    {
        $categories = array();
        $category_ids = $product->get_category_ids();
        if (!empty($category_ids)) {
            foreach ($category_ids as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $categories[] = $term->name;
                }
            }
        }

        return array(
            'id'         => $product->get_id(),
            'sku'        => $product->get_sku() ?: (string) $product->get_id(),
            'name'       => $product->get_name(),
            'price'      => (float) $product->get_price(),
            'categories' => $categories,
            'category'   => !empty($categories) ? $categories[0] : '',
        );
    }

    /**
     * Output setEcommerceView script in wp_head BEFORE main tracking code
     * This ensures the ecommerce view is set before trackPageView is called
     * Prevents duplicate page view tracking
     */
    public function outputEcommerceViewScript()
    {
        // Only output on product or category pages
        if (!is_product() && !is_product_category()) {
            return;
        }

        echo "\n<!-- Matomo Site Kit - Ecommerce View (before trackPageView) -->\n";
        echo "<script>\n";
        echo "var _paq = window._paq = window._paq || [];\n";

        if (is_product()) {
            global $product;
            if ($product instanceof WC_Product) {
                $data = $this->getProductData($product);
                echo "_paq.push(['setEcommerceView',\n";
                echo "  " . wp_json_encode($data['sku']) . ",\n";
                echo "  " . wp_json_encode($data['name']) . ",\n";
                echo "  " . wp_json_encode($data['categories']) . ",\n";
                echo "  " . (float) $data['price'] . "\n";
                echo "]);\n";
            }
        } elseif (is_product_category()) {
            $term = get_queried_object();
            if ($term && isset($term->name)) {
                echo "_paq.push(['setEcommerceView',\n";
                echo "  false,\n";
                echo "  false,\n";
                echo "  " . wp_json_encode($term->name) . "\n";
                echo "]);\n";
            }
        }

        echo "</script>\n";
        echo "<!-- End Matomo Site Kit - Ecommerce View -->\n";
    }

    /**
     * Track product view
     */
    public function trackProductView()
    {
        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $data = $this->getProductData($product);

        // Server-side tracking - use setEcommerceView then track page view
        $tracker = $this->getTracker();
        if ($tracker) {
            $tracker->setEcommerceView(
                $data['sku'],
                $data['name'],
                $data['categories'],
                $data['price']
            );
            $tracker->doTrackPageView($data['name']);
        }

        // Queue for JS/dataLayer output
        $this->queueEvent('view_item', $data);
    }

    /**
     * Track add to cart
     *
     * @param string $cart_item_key
     * @param int $product_id
     * @param int $quantity
     * @param int $variation_id
     * @param array $variation
     * @param array $cart_item_data
     */
    public function trackAddToCart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $product = wc_get_product($variation_id ?: $product_id);

        if (!$product) {
            return;
        }

        $data = $this->getProductData($product);
        $data['quantity'] = $quantity;

        // Server-side tracking - update cart
        $tracker = $this->getTracker();
        if ($tracker) {
            $cart = WC()->cart;
            if ($cart) {
                // Add all cart items
                foreach ($cart->get_cart() as $item) {
                    $item_product = $item['data'];
                    if ($item_product) {
                        $item_data = $this->getProductData($item_product);
                        $tracker->addEcommerceItem(
                            $item_data['sku'],
                            $item_data['name'],
                            $item_data['categories'],
                            $item_data['price'],
                            $item['quantity']
                        );
                    }
                }
                $tracker->doTrackEcommerceCartUpdate((float) $cart->get_total('edit'));
            }
        }

        // Queue for JS/dataLayer
        $this->queueEvent('add_to_cart', $data);
    }

    /**
     * Track remove from cart
     *
     * @param string $cart_item_key
     * @param WC_Cart $cart
     */
    public function trackRemoveFromCart($cart_item_key, $cart)
    {
        $cart_item = $cart->get_cart_item($cart_item_key);

        if (empty($cart_item)) {
            return;
        }

        $product = $cart_item['data'];
        if (!$product) {
            return;
        }

        $data = $this->getProductData($product);
        $data['quantity'] = $cart_item['quantity'];

        // Queue for JS/dataLayer
        $this->queueEvent('remove_from_cart', $data);
    }

    /**
     * Track cart update
     *
     * @param string $cart_item_key
     * @param int $quantity
     * @param int $old_quantity
     * @param WC_Cart $cart
     */
    public function trackCartUpdate($cart_item_key, $quantity, $old_quantity, $cart)
    {
        // Server-side cart update
        // Note: Each call to doTrackEcommerceCartUpdate automatically clears ecommerceItems
        // So we need a fresh tracker instance for each update
        $tracker = $this->getTracker();
        if ($tracker) {
            foreach ($cart->get_cart() as $item) {
                $product = $item['data'];
                if ($product) {
                    $item_data = $this->getProductData($product);
                    $tracker->addEcommerceItem(
                        $item_data['sku'],
                        $item_data['name'],
                        $item_data['categories'],
                        $item_data['price'],
                        $item['quantity']
                    );
                }
            }

            $tracker->doTrackEcommerceCartUpdate((float) $cart->get_total('edit'));
        }
    }

    /**
     * Track order completion
     *
     * @param int $order_id
     */
    public function trackOrderComplete($order_id)
    {
        // Check if already tracked (avoid duplicate tracking on page refresh)
        $tracked = get_post_meta($order_id, '_omsk_tracked', true);
        if ($tracked) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $items = array();
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $item_data = $this->getProductData($product);
                $item_data['quantity'] = $item->get_quantity();
                $item_data['line_total'] = (float) $item->get_total();
                $items[] = $item_data;
            }
        }

        $order_data = array(
            'transaction_id' => $order->get_order_number(),
            'value'          => (float) $order->get_total(),
            'subtotal'       => (float) $order->get_subtotal(),
            'tax'            => (float) $order->get_total_tax(),
            'shipping'       => (float) $order->get_shipping_total(),
            'discount'       => (float) $order->get_discount_total(),
            'currency'       => $order->get_currency(),
            'items'          => $items,
        );

        // Server-side tracking
        $tracker = $this->getTracker();
        if ($tracker) {
            foreach ($items as $item) {
                $tracker->addEcommerceItem(
                    $item['sku'],
                    $item['name'],
                    $item['categories'],
                    $item['price'],
                    $item['quantity']
                );
            }

            $tracker->doTrackEcommerceOrder(
                $order_data['transaction_id'],
                $order_data['value'],
                $order_data['subtotal'],
                $order_data['tax'],
                $order_data['shipping'],
                $order_data['discount']
            );
        }

        // Queue for JS/dataLayer
        $this->queueEvent('purchase', $order_data);

        // Mark as tracked
        update_post_meta($order_id, '_omsk_tracked', 1);
    }

    /**
     * Track category view
     */
    public function trackCategoryView()
    {
        if (!is_product_category()) {
            return;
        }

        $term = get_queried_object();
        if (!$term || !isset($term->name)) {
            return;
        }

        // Server-side tracking
        // For category views, use setEcommerceView with only category (SKU and name empty)
        $tracker = $this->getTracker();
        if ($tracker) {
            $tracker->setEcommerceView('', '', $term->name);
            $tracker->doTrackPageView($term->name);
        }

        // Queue for JS/dataLayer
        $this->queueEvent('view_item_list', array(
            'item_list_id'   => $term->term_id,
            'item_list_name' => $term->name,
        ));
    }

    /**
     * Queue an event for JS/dataLayer output
     *
     * @param string $event Event name
     * @param array $data Event data
     */
    private function queueEvent($event, $data)
    {
        if (!$this->enableJsTracking && !$this->enableDataLayer) {
            return;
        }

        $queue = WC()->session ? WC()->session->get('omsk_ecommerce_events', array()) : array();
        $queue[] = array(
            'event' => $event,
            'data'  => $data,
        );

        if (WC()->session) {
            WC()->session->set('omsk_ecommerce_events', $queue);
        }
    }

    /**
     * Output tracking scripts in footer
     */
    public function outputTrackingScripts()
    {
        // Get queued events
        $events = WC()->session ? WC()->session->get('omsk_ecommerce_events', array()) : array();

        // Clear queue
        if (WC()->session) {
            WC()->session->set('omsk_ecommerce_events', array());
        }

        if (empty($events) && !is_product() && !is_product_category() && !is_checkout()) {
            return;
        }

        // Add current page context for product/category pages
        if (is_product() && empty($events)) {
            global $product;
            if ($product instanceof WC_Product) {
                $data = $this->getProductData($product);
                $events[] = array('event' => 'view_item', 'data' => $data);
            }
        }

        if (is_product_category() && empty($events)) {
            $term = get_queried_object();
            if ($term && isset($term->name)) {
                $events[] = array(
                    'event' => 'view_item_list',
                    'data'  => array(
                        'item_list_id'   => $term->term_id,
                        'item_list_name' => $term->name,
                    ),
                );
            }
        }

        if (empty($events)) {
            return;
        }

        $currency = get_woocommerce_currency();

        echo "\n<!-- Matomo Site Kit - WooCommerce Ecommerce Tracking -->\n";
        echo "<script>\n";

        foreach ($events as $event_data) {
            $event = $event_data['event'];
            $data = $event_data['data'];

            // DataLayer push (GA4 format)
            if ($this->enableDataLayer) {
                $this->outputDataLayerEvent($event, $data, $currency);
            }

            // Matomo JS tracking
            if ($this->enableJsTracking) {
                $this->outputMatomoJsEvent($event, $data, $currency);
            }
        }

        echo "</script>\n";
        echo "<!-- End Matomo Site Kit - WooCommerce Ecommerce Tracking -->\n";
    }

    /**
     * Output dataLayer event in GA4 format for Matomo Tag Manager
     * Uses _mtm array which is Matomo Tag Manager's data layer
     *
     * @param string $event
     * @param array $data
     * @param string $currency
     */
    private function outputDataLayerEvent($event, $data, $currency)
    {
        echo "window._mtm = window._mtm || [];\n";

        switch ($event) {
            case 'view_item':
                $item = $this->formatGA4Item($data, $currency);
                echo "_mtm.push({ ecommerce: null });\n";
                echo "_mtm.push({\n";
                echo "  event: 'view_item',\n";
                echo "  ecommerce: {\n";
                echo "    currency: " . wp_json_encode($currency) . ",\n";
                echo "    value: " . (float) $data['price'] . ",\n";
                echo "    items: [" . wp_json_encode($item) . "]\n";
                echo "  }\n";
                echo "});\n";
                break;

            case 'add_to_cart':
                $item = $this->formatGA4Item($data, $currency);
                echo "_mtm.push({ ecommerce: null });\n";
                echo "_mtm.push({\n";
                echo "  event: 'add_to_cart',\n";
                echo "  ecommerce: {\n";
                echo "    currency: " . wp_json_encode($currency) . ",\n";
                echo "    value: " . ((float) $data['price'] * (int) $data['quantity']) . ",\n";
                echo "    items: [" . wp_json_encode($item) . "]\n";
                echo "  }\n";
                echo "});\n";
                break;

            case 'remove_from_cart':
                $item = $this->formatGA4Item($data, $currency);
                echo "_mtm.push({ ecommerce: null });\n";
                echo "_mtm.push({\n";
                echo "  event: 'remove_from_cart',\n";
                echo "  ecommerce: {\n";
                echo "    currency: " . wp_json_encode($currency) . ",\n";
                echo "    value: " . ((float) $data['price'] * (int) $data['quantity']) . ",\n";
                echo "    items: [" . wp_json_encode($item) . "]\n";
                echo "  }\n";
                echo "});\n";
                break;

            case 'view_item_list':
                echo "_mtm.push({ ecommerce: null });\n";
                echo "_mtm.push({\n";
                echo "  event: 'view_item_list',\n";
                echo "  ecommerce: {\n";
                echo "    item_list_id: " . wp_json_encode((string) $data['item_list_id']) . ",\n";
                echo "    item_list_name: " . wp_json_encode($data['item_list_name']) . "\n";
                echo "  }\n";
                echo "});\n";
                break;

            case 'purchase':
                $items = array();
                foreach ($data['items'] as $item) {
                    $items[] = $this->formatGA4Item($item, $currency);
                }
                echo "_mtm.push({ ecommerce: null });\n";
                echo "_mtm.push({\n";
                echo "  event: 'purchase',\n";
                echo "  ecommerce: {\n";
                echo "    transaction_id: " . wp_json_encode($data['transaction_id']) . ",\n";
                echo "    value: " . (float) $data['value'] . ",\n";
                echo "    tax: " . (float) $data['tax'] . ",\n";
                echo "    shipping: " . (float) $data['shipping'] . ",\n";
                echo "    currency: " . wp_json_encode($data['currency']) . ",\n";
                echo "    items: " . wp_json_encode($items) . "\n";
                echo "  }\n";
                echo "});\n";
                break;
        }
    }

    /**
     * Format item for GA4 dataLayer
     *
     * @param array $data
     * @param string $currency
     * @return array
     */
    private function formatGA4Item($data, $currency)
    {
        $item = array(
            'item_id'   => $data['sku'],
            'item_name' => $data['name'],
            'price'     => (float) $data['price'],
            'currency'  => $currency,
        );

        if (!empty($data['category'])) {
            $item['item_category'] = $data['category'];
        }

        if (!empty($data['categories']) && count($data['categories']) > 1) {
            for ($i = 1; $i < min(count($data['categories']), 5); $i++) {
                $item['item_category' . ($i + 1)] = $data['categories'][$i];
            }
        }

        if (isset($data['quantity'])) {
            $item['quantity'] = (int) $data['quantity'];
        }

        return $item;
    }

    /**
     * Output Matomo JS tracking event
     *
     * Note: view_item and view_item_list are handled in outputEcommerceViewScript()
     * which runs in wp_head BEFORE the main tracking code's trackPageView.
     * This prevents duplicate page view tracking.
     *
     * @param string $event
     * @param array $data
     * @param string $currency
     */
    private function outputMatomoJsEvent($event, $data, $currency)
    {
        echo "var _paq = window._paq = window._paq || [];\n";

        switch ($event) {
            case 'view_item':
            case 'view_item_list':
                // These are handled in outputEcommerceViewScript() in wp_head
                // to ensure setEcommerceView is called BEFORE trackPageView
                break;

            case 'add_to_cart':
            case 'remove_from_cart':
                // For cart changes, we'd need to rebuild the entire cart
                // This is handled by cart update tracking
                break;

            case 'purchase':
                // Add items to the order
                foreach ($data['items'] as $item) {
                    echo "_paq.push(['addEcommerceItem',\n";
                    echo "  " . wp_json_encode($item['sku']) . ",\n";
                    echo "  " . wp_json_encode($item['name']) . ",\n";
                    echo "  " . wp_json_encode($item['categories']) . ",\n";
                    echo "  " . (float) $item['price'] . ",\n";
                    echo "  " . (int) $item['quantity'] . "\n";
                    echo "]);\n";
                }
                // Track the order
                echo "_paq.push(['trackEcommerceOrder',\n";
                echo "  " . wp_json_encode($data['transaction_id']) . ",\n";
                echo "  " . (float) $data['value'] . ",\n";
                echo "  " . (float) $data['subtotal'] . ",\n";
                echo "  " . (float) $data['tax'] . ",\n";
                echo "  " . (float) $data['shipping'] . ",\n";
                echo "  " . (float) $data['discount'] . "\n";
                echo "]);\n";
                break;
        }
    }
}
