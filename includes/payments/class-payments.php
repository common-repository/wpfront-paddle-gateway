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

namespace WPFront\Paddle;

if (!defined('ABSPATH')) {
    exit();
}

require __DIR__ . '/class-admin-payments.php';
require __DIR__ . '/class-payments-entity.php';

use \WPFront\Paddle\Entities\Payments_Entity;

if (!class_exists('\WPFront\Paddle\Payments')) {

    class Payments {

        protected static $instance = null;

        const STATUS_PENDING = 1;
        const STATUS_COMPLETED = 2;
        const STATUS_REFUNDED = 3;
        const STATUS_ABANDONED = 4;

        const CRON_ABANDONED_NAME = 'wpfront_paddle_gateway_update_abandoned';
        
        protected function __construct() {
            
        }

        /**
         * Returns class instance.
         *
         * @return Payments
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new Payments();
            }

            return self::$instance;
        }

        /**
         * 
         * @param WPFront_Paddle_Gateway $main
         */
        public function init($main) {
            new Admin\Admin_Payments($main);

            add_filter('paddle_generate_paylink_payload', array($this, 'generate_paylink_payload'), 10, 2);
            add_action('paddle_payment_succeeded', array($this, 'payment_succeeded'));
            add_action('paddle_subscription_payment_succeeded', array($this, 'payment_succeeded'));
            add_action('paddle_payment_refunded', array($this, 'payment_refunded'));
            add_action('paddle_subscription_payment_refunded', array($this, 'payment_refunded'));

            add_action(self::CRON_ABANDONED_NAME, array($this, 'process_abandoned_payments'));
            if (!wp_next_scheduled(self::CRON_ABANDONED_NAME)) {
                wp_schedule_event(time(), 'daily', self::CRON_ABANDONED_NAME);
            }

            register_deactivation_hook($main->get_plugin_file(), array($this, 'cron_deactivate'));
        }

        public function generate_paylink_payload($payload, $entity) {
            $email = '';
            $user_id = '';
            $customer_name = '';
            if (is_user_logged_in()) {
                $user_data = wp_get_current_user();
                $user_id = $user_data->ID;
                $email = $user_data->user_email;
                $customer_name = $user_data->display_name;
            }

            $paylink_id = $entity->id;
            $payload['passthrough'] = 'PAYLINK-' . $paylink_id . '-' . ($this->guid());
            if (isset($payload['title'])) {
                $product_name = $payload['title'];
            } else {
                $product_name = $entity->title;
            }
            $asking_price = $payload['price'];
            $payload['customer_email'] = $email;

            $entity = new Payments_Entity();

            $entity->paylink_id = $paylink_id;
            $entity->date = current_time('mysql');
            $entity->user_id = $user_id;
            $entity->status = self::STATUS_PENDING;
            $entity->passthrough = $payload['passthrough'];
            $entity->customer_name = $customer_name;
            $entity->email = $email;
            $entity->product_name = $product_name;
            $entity->quantity = 1;
            $entity->currency = $payload['currency'];
            $entity->asking_price = $asking_price;

            $entity->add();

            if (!empty($payload['return_url'])) {
                $payload['return_url'] = add_query_arg('payment-id', $entity->id, $payload['return_url']);
            }

            return $payload;
        }

        public function payment_succeeded() {
            $passthrough = sanitize_text_field($_POST['passthrough']);

            $data = null;
            if (strpos($passthrough, 'PAYLINK-') !== false) {
                $entity = new Payments_Entity();
                $status = self::STATUS_PENDING;
                $data = $entity->get_payments_by_passthrough($passthrough, $status);
                if (!empty($data)) {
                    $data = $data[0];
                }
            }


            if (empty($data)) {
                $data = new Payments_Entity();

                $data->date = current_time('mysql');
                $data->passthrough = sanitize_text_field($_POST['passthrough']);
                $data->asking_price = floatval($_POST['earnings']) + floatval($_POST['fee']); 
                $user_data = get_user_by('email', sanitize_email($_POST['email']));

                if (!empty($user_data)) {
                    $data->user_id = $user_data->ID;
                }
            }

            if (empty($_POST['product_name'])) {
                $data->product_name = sanitize_text_field($_POST['plan_name']);
            } else {
                $data->product_name = sanitize_text_field($_POST['product_name']);
            }
            $data->checkout_id = sanitize_text_field($_POST['checkout_id']);
            $data->order_id = sanitize_text_field($_POST['order_id']);
            $data->customer_name = sanitize_text_field($_POST['customer_name']);
            $data->email = sanitize_email($_POST['email']);
            $data->country = sanitize_text_field($_POST['country']);
            $data->coupon = sanitize_text_field($_POST['coupon']);
            $data->payment_method = sanitize_text_field($_POST['payment_method']);
            $data->currency = sanitize_text_field($_POST['currency']);
            $data->product_id = sanitize_text_field($_POST['product_id']);
            $data->sale_gross = floatval($_POST['sale_gross']);
            $data->receipt_url = esc_url_raw($_POST['receipt_url']);
            $data->quantity = sanitize_text_field($_POST['quantity']);
            $data->balance_currency = sanitize_text_field($_POST['balance_currency']); 
            $data->balance_gross = floatval($_POST['balance_gross']);
            $data->balance_fee = floatval($_POST['balance_fee']);
            $data->balance_tax = floatval($_POST['balance_tax']);
            $data->balance_earnings = floatval($_POST['balance_earnings']);
            $data->payload[] = $_POST;
            $data->status = self::STATUS_COMPLETED;

            $data->update();
        }

        public function payment_refunded() {
            $checkout_id = sanitize_text_field($_POST['checkout_id']);
            $refund_type = sanitize_text_field($_POST['refund_type']);
            $order_id = sanitize_text_field($_POST['order_id']);
            $entity = new Payments_Entity();

            $data = $entity->get_payment_by_order_id($order_id);

            if (!empty($data)) {

                if (!empty($data->balance_earnings)) {
                    $data->balance_gross = $data->balance_gross - floatval($_POST['balance_gross_refund']); 
                    $data->balance_fee = $data->balance_fee - floatval($_POST['balance_fee_refund']);
                    $data->balance_tax = $data->balance_tax - floatval($_POST['balance_tax_refund']);
                    $data->balance_earnings = $data->balance_earnings - floatval($_POST['balance_earnings_decrease']);
                    $data->sale_gross = $data->sale_gross - floatval($_POST['gross_refund']);
                }

                $data->payload[] = $_POST;

                $data->status = self::STATUS_COMPLETED;
                if ($refund_type === 'full') {
                    $data->status = self::STATUS_REFUNDED;
                }

                $data->update();
            }
        }

        public function process_abandoned_payments() {
            $to = date('Y-m-d H:i:s', strtotime('-1 week'));
            $from = date('Y-m-d H:i:s', strtotime($to . '-1 year'));
            $status = self::STATUS_PENDING;

            $entity = new Payments_Entity();
            do {
                $entities = $entity->get_payments_by_date($from, $to, $status, 50);

                foreach ($entities as $e) {
                    $e->status = self::STATUS_ABANDONED;
                    $e->update();
                }
            } while (count($entities) >= 50);
        }

        protected function guid() {
            if (function_exists('com_create_guid') === true) {
                return trim(com_create_guid(), '{}');
            }

            return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }

        public function cron_deactivate() {
            $timestamp = wp_next_scheduled(self::CRON_ABANDONED_NAME);
            wp_unschedule_event($timestamp, self::CRON_ABANDONED_NAME);
        }

    }

}
    