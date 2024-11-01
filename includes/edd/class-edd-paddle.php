<?php

/*
  WPFront Paddle Gateway Plugin
  Copyright (C) 2021, WPFront.com
  Website: wpfront.com
  Contact: syam@wpfront.com

  WPFront Paddle Gateway Plugin is distributed under the GNU General Public License, Version 3,
  June 2007. Copyright (C) 2007 Free Software Foundation, Inc., 51 Franklin
  St, Fifth Floor, Boston, MA 02110, USA

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace WPFront\Paddle\EDD;

if (!defined('ABSPATH')) {
    exit();
}

require __DIR__ . '/settings/class-edd-settings.php';
require __DIR__ . '/frontend/class-edd-frontend.php';

use WPFront\Paddle\WPFront_Paddle_Gateway;
use \WPFront\Paddle\Settings;
use \WPFront\Paddle\Paddle_API;

if (!class_exists('\WPFront\Paddle\EDD\EDD_Paddle')) {


    class EDD_Paddle {

        protected static $instance = null;

        const VERSION = '1.0';
        const GATEWAY_KEY = 'wpfront-paddle-gateway';

        protected function __construct() {
            
        }

        /**
         * Returns class instance.
         *
         * @return EDD_Paddle
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new EDD_Paddle();
            }

            return self::$instance;
        }

        public function init($main) {
            add_filter('edd_payment_gateways', array($this, 'edd_payment_gateways'));
            add_action('paddle_payment_succeeded', array($this, 'edd_payment_succeeded'));
            add_action('paddle_payment_refunded', array($this, 'edd_payment_refunded'));
            add_filter('edd_use_taxes', array($this, 'edd_use_taxes'));
            add_filter('edd_payment_details_transaction_id-' . self::GATEWAY_KEY, array($this, 'edd_payment_details_transaction_id'));

            new EDD_Settings();
            new EDD_Frontend();

            if (class_exists('\WPFront\Paddle\Pro\Subscription_Plan')) {
                \WPFront\Paddle\Pro\Subscription_Plan::instance()->init($main);
            }

            if (class_exists('\WPFront\Paddle\Pro\EDD\EDD_Paddle_Pro')) {
                \WPFront\Paddle\Pro\EDD\EDD_Paddle_Pro::instance()->init($this);
            }
        }

        public function load_recurring() {
            if (class_exists('\EDD_Recurring_Gateway')) {
                require dirname(__DIR__) . '/pro/edd/recurring/class-edd-recurring.php';
                $recurring = new \WPFront\Paddle\Pro\EDD\EDD_Recurring();
                $recurring->init();
            }
        }

        public function edd_payment_gateways($gateways) {
            $supports = apply_filters('wpfront_paddle_gateway_edd_supports', array());

            $gateways[WPFront_Paddle_Gateway::GATEWAY_KEY] = array(
                'admin_label' => __('Paddle', 'wpfront-paddle-gateway'),
                'checkout_label' => $this->get_checkout_label(),
                'supports' => $supports
            );

            return $gateways;
        }

        public function get_checkout_label() {
            $label = Settings::instance()->get_setting('checkout_label');
            if (empty($label)) {
                $label = __('Paddle', 'wpfront-paddle-gateway');
            }
            return $label;
        }

        public function edd_use_taxes() {
            return false;
        }

        public function edd_payment_succeeded() {
            $passthrough = sanitize_text_field($_POST['passthrough']);
            $payment = null;
            if (strpos($passthrough, 'EDD-') !== false) {
                $payment_id = intval(substr($passthrough, 4));
                $total_amount = floatval($_POST['sale_gross']);
                $order_id = sanitize_text_field($_POST['order_id']);
                $payment = edd_get_payment($payment_id);
            }
            if (empty($payment)) {
                return;
            }

            $email = '';
            if (!empty($_POST['email'])) {
                $email = sanitize_email($_POST['email']);
            }

            if (!in_array($payment->status, ['publish', 'complete'])) {
                $payment->update_status('complete');
            }

            $payment->gateway = EDD_Paddle::GATEWAY_KEY;

            if (empty($payment->email)) {
                $payment->email = $email;
            }

            if (!empty($total_amount)) {
                $payment->total = $total_amount;
            }

            $result = $payment->save();

            $balance_currency = sanitize_text_field($_POST['balance_currency']);
            $currency = sanitize_text_field($_POST['currency']);
            if ($balance_currency != $currency) {
                edd_insert_payment_note($payment->ID, sprintf(__('Balance currency(%s) gross amount = %s', 'wpfront-paddle-gateway'), $balance_currency, floatval($_POST['balance_gross'])));
            }

            edd_set_payment_transaction_id($payment_id, $order_id);
            $this->set_payment_receipt_url($payment, esc_url_raw($_POST['receipt_url']));
        }

        public function edd_payment_refunded() {
            $passthrough = sanitize_text_field($_POST['passthrough']);

            if (strpos($passthrough, 'EDD-') !== false) {
                $payment_id = intval(substr($passthrough, 4));
                $amount = floatval($_POST['amount']);
            } else {
                return;
            }

            if (($_POST['refund_type'] == 'vat' || $_POST['refund_type'] == 'partial') && edd_get_currency() == sanitize_text_field($_POST['balance_currency'])) {
                $payment->total = $payment->total - floatval($_POST['balance_gross_refund']);

                if ($payment->total < 0) {
                    $payment->total = 0;
                }
            }

            switch ($_POST['refund_type']) {
                case 'vat':
                    edd_insert_payment_note($payment_id, sprintf(__('VAT refund of %s processed in Paddle', 'wpfront-paddle-gateway'), edd_currency_filter($amount)));
                    break;
                case 'partial':
                    edd_insert_payment_note($payment_id, sprintf(__('Partial refund of %s processed in Paddle', 'wpfront-paddle-gateway'), edd_currency_filter($amount)));
                    break;
                case 'full':
                    edd_update_payment_status($payment_id, 'refunded');
                    edd_insert_payment_note($payment_id, __('Paddle Gateway: Full refund issued.', 'wpfront-paddle-gateway'));
                    break;
                default:
                    error_log("WPFront-Paddle-Gateway: Undefined refund_type received from Paddle. refund_type: '{$_POST['refund_type']}'");
                    edd_insert_payment_note($payment_id, sprintf(__('Paddle Gateway: Undefined refund_type received from Paddle. refund_type: "%s"', 'wpfront-paddle-gateway'), $_POST['refund_type']));
                    break;
            }
        }

        public function edd_payment_details_transaction_id($transaction_id) {
            $paddle_url = Paddle_API::instance()->get_paddle_api_url('/orders/detail/');
            $transaction_id_link_url = '<a href="' . esc_url($paddle_url . $transaction_id) . '" target="_blank">' . $transaction_id . '</a>';
            return $transaction_id_link_url;
        }

        public function set_subscription_update_url($subscription, $url) {
            edd_update_payment_meta($subscription->parent_payment_id, '_edd_paddle_subscription_update_url', $url);
        }

        public function get_subscription_update_url($subscription) {
            if ($subscription->gateway == EDD_Paddle::GATEWAY_KEY) {
                $url = edd_get_payment_meta($subscription->parent_payment_id, '_edd_paddle_subscription_update_url');
                if (empty($url)) {
                    return null;
                }

                return $url;
            }

            return null;
        }

        public function set_payment_receipt_url($payment, $url) {
            edd_update_payment_meta($payment->ID, '_edd_paddle_payment_receipt_url', $url);
        }

        public function get_payment_receipt_url($payment) {
            $url = edd_get_payment_meta($payment->ID, '_edd_paddle_payment_receipt_url');
            if (empty($url)) {
                return null;
            }

            return $url;
        }

    }

}
