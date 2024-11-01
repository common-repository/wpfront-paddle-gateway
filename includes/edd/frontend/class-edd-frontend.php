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

use WPFront\Paddle\WPFront_Paddle_Gateway;
use WPFront\Paddle\EDD\EDD_Settings;
use WPFront\Paddle\Webhook;
use WPFront\Paddle\EDD\EDD_Paddle;
use WPFront\Paddle\Paddle_API;

if (!class_exists('\WPFront\Paddle\EDD\EDD_Frontend')) {

    class EDD_Frontend {

        public function __construct() {
            $key = WPFront_Paddle_Gateway::GATEWAY_KEY;

            add_action("edd_gateway_{$key}", array($this, 'process_payment')); //process cart payment
            add_action("edd_{$key}_cc_form", '__return_false'); //remove form from checkout page
            add_action('edd_payment_receipt_after', array($this, 'edd_payment_receipt')); //add invoice link to purchase history page
            add_filter('edd_payment_confirm_' . EDD_Paddle::GATEWAY_KEY, array($this, 'edd_payment_confirm')); //show payment pending page
            add_filter("edd_get_payment_transaction_id-{$key}", array($this, 'get_payment_transaction_id')); //add 'complete payment' in purchase history page
        }

        public function process_payment($data) {
            $vendor_id = EDD_Settings::instance()->get_vendor_id();
            $auth_code = EDD_Settings::instance()->get_auth_code();

            if (empty($vendor_id) || empty($auth_code)) {
                $this->error = __('Paddle Vendor ID/Auth Code is missing. Please configure it in payment gateway settings.', 'wpfront-paddle-gateway');
            } else {
                $payment_data = array(
                    'price' => $data['price'],
                    'date' => $data['date'],
                    'user_email' => $data['post_data']['edd_email'],
                    'purchase_key' => $data['purchase_key'],
                    'currency' => edd_get_currency(),
                    'downloads' => $data['downloads'],
                    'cart_details' => $data['cart_details'],
                    'user_info' => $data['user_info'],
                    'status' => 'pending'
                );

                $payment_id = edd_insert_payment($payment_data);

                $pay_link = $this->get_paddle_pay_link($payment_id, $data);

                if (!empty($pay_link)) {
                    edd_empty_cart();
                    wp_redirect($pay_link);
                    exit;
                }
            }

            if (!empty($this->error)) {
                edd_set_error('paddle_generate_pay_link_failed', $this->error);
            } else {
                edd_set_error('paddle_generate_pay_link_failed', __('Failed to generate Paddle pay link.', 'wpfront-paddle-gateway'));
            }

            edd_send_back_to_checkout('?payment-mode=' . EDD_Paddle::GATEWAY_KEY);
        }

        private function get_paddle_pay_link($payment_id, $data) {
            //product name 
            $purchase_summary = '';
            if (!empty($data['cart_details']) && is_array($data['cart_details'])) {
                foreach ($data['cart_details'] as $item) {
                    $purchase_summary .= $item['name'];
                    $price_id = isset($item['item_number']['options']['price_id']) ? intval($item['item_number']['options']['price_id']) : false;
                    if ($price_id !== false) {
                        $purchase_summary .= ' - ' . edd_get_price_option_name($item['id'], $item['item_number']['options']['price_id']);
                    }
                    $purchase_summary .= ', ';
                }

                $purchase_summary = rtrim($purchase_summary, ', ');
                $purchase_summary = rtrim($purchase_summary, ' - ');
            } else {
                $purchase_summary = edd_get_purchase_summary($data, false);
            }

            $amount = edd_get_payment_amount($payment_id);

            $webhook_url = Webhook::instance()->get_webhook_url();

            $redirect_url = add_query_arg(['payment-confirmation' => EDD_Paddle::GATEWAY_KEY, 'payment-id' => $payment_id], edd_get_success_page_uri());

            $payload = [
                'title' => $purchase_summary,
                'quantity_variable' => 0,
                'quantity' => 1,
                'customer_email' => $data['user_email'],
                'prices' => [sprintf('%s:%s', edd_get_currency(), $amount)],
                'passthrough' => 'EDD-'.$payment_id,
                'webhook_url' => $webhook_url,
                'return_url' => $redirect_url,
            ];

            $paylink = Paddle_API::instance()->get_pay_link($payload);

            if(is_wp_error($paylink)) {
                $this->error = $paylink->get_error_message();
                return null;
            }
            
            return $paylink;
        }

        public function edd_payment_receipt($payment) {
            $receipt_url = EDD_Paddle::instance()->get_payment_receipt_url($payment);
            if (empty($receipt_url)) {
                return;
            }
            ?>
            <tr>
                <td><strong><?php esc_html_e('Invoice', 'wpfront-paddle-gateway'); ?>:</strong></td>
                <td>
                    <a href="<?php echo esc_url($receipt_url); ?>" target="_blank"><?php esc_html_e('View', 'wpfront-paddle-gateway'); ?></a>
                </td>
            </tr>
            <?php
        }

        public function edd_payment_confirm($content) {
            if (!isset($_GET['payment-confirmation'], $_GET['payment-id'])) {
                return $content;
            }

            if ($_GET['payment-confirmation'] !== EDD_Paddle::GATEWAY_KEY) {
                return $content;
            }

            $payment_status = edd_get_payment_status(intval($_GET['payment-id']));
            if ($payment_status !== 'pending') {
                return $content;
            }

            ob_start();
            edd_get_template_part('payment', 'processing');
            $content = ob_get_clean();

            return $content;
        }

        public function get_payment_transaction_id($payment_id) {
            if (!empty($this->in_get_payment_transaction_id)) {
                return $payment_id;
            }

            $this->in_get_payment_transaction_id = true;

            $payment = new \EDD_Payment($payment_id);

            $transaction_id = '';

            $recoverable_statuses = apply_filters('edd_recoverable_payment_statuses', array('pending', 'abandoned', 'failed'));
            if (!in_array($payment->status, $recoverable_statuses)) {
                $transaction_id = $payment->get_meta('_edd_payment_transaction_id', true);
            }

            $this->in_get_payment_transaction_id = false;

            return $transaction_id;
        }

    }

}
