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

if (!class_exists('\WPFront\Paddle\Paddle_API')) {

    class Paddle_API {

        protected static $instance = null;

        /**
         * Returns class instance.
         *
         * @return Paddle_API
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new Paddle_API();
            }

            return self::$instance;
        }

        public function __construct() {
            
        }

        public function get_pay_link($data) {
            $credentials = $this->get_paddle_credentials();

            $defaults = array(
                'vendor_id' => $credentials['vendor_id'],
                'vendor_auth_code' => $credentials['auth_code'],
                'quantity_variable' => 0,
                'quantity' => 1,
                'passthrough' => '-1',
                'webhook_url' => Webhook::instance()->get_webhook_url()
            );

            $payload = wp_parse_args($data, $defaults);

            if (isset($payload['product_id'])) {
                unset($payload['title']);
                unset($payload['webhook_url']);
            }

            if (isset($data['currency'], $data['price'])) {
                $payload['prices'] = [sprintf('%s:%s', $data['currency'], $data['price'])];
                unset($payload['currency']);
                unset($payload['price']);
            }

            $post = wp_remote_post(
                    $this->get_paddle_api_url('/api/2.0/product/generate_pay_link'),
                    [
                        'body' => $payload,
                        'timeout' => 20,
                    ]
            );

            $response = json_decode(wp_remote_retrieve_body($post), true);
            if ($response['success']) {
                return $response['response']['url'];
            }

            error_log(sprintf('WPFront Paddle Gateway Error - %s: %s', $response['error']['code'], $response['error']['message']));

            wp_cache_flush();
            
            return new \WP_Error($response['error']['code'], $response['error']['message']);
        }

        public function get_subscription_plans($data = array()) {
            $credentials = $this->get_paddle_credentials();

            $defaults = [
                'vendor_id' => $credentials['vendor_id'],
                'vendor_auth_code' => $credentials['auth_code'],
            ];

            $payload = wp_parse_args($data, $defaults);

            $request = wp_remote_post(
                    $this->get_paddle_api_url('/api/2.0/subscription/plans'),
                    [
                        'body' => $payload,
                        'timeout' => 10,
                    ]
            );

            $response = json_decode(wp_remote_retrieve_body($request), true);

            if ($response['success'] == true) {
                return $response['response'];
            }

            error_log(sprintf('WP Paddle Gateway Error - %s: %s', $response['error']['code'], $response['error']['message']));

            return new \WP_Error($response['error']['code'], $response['error']['message']);
        }

        public function create_subscription_plan($data) {
            $credentials = $this->get_paddle_credentials();

            $defaults = [
                'vendor_id' => $credentials['vendor_id'],
                'vendor_auth_code' => $credentials['auth_code']
            ];

            $payload = wp_parse_args($data, $defaults);

            $request = wp_remote_post(
                    $this->get_paddle_api_url('/api/2.0/subscription/plans_create'),
                    [
                        'body' => $payload,
                        'timeout' => 10,
                    ]
            );

            $response = json_decode(wp_remote_retrieve_body($request), true);

            if ($response['success'] == true) {
                return $response['response']['product_id'];
            }

            error_log(sprintf('WP Paddle Gateway Error - %s: %s', $response['error']['code'], $response['error']['message']));

            return new \WP_Error($response['error']['code'], $response['error']['message']);
        }

        public function cancel_subscription($data) {
            $credentials = $this->get_paddle_credentials();

            $defaults = [
                'vendor_id' => $credentials['vendor_id'],
                'vendor_auth_code' => $credentials['auth_code']
            ];

            $payload = wp_parse_args($data, $defaults);

            $request = wp_remote_post(
                    $this->get_paddle_api_url('/api/2.0/subscription/users_cancel'),
                    [
                        'body' => $payload,
                        'timeout' => 10
                    ]
            );

            $response = json_decode(wp_remote_retrieve_body($request), true);

            if (isset($response['success']) && $response['success']) {
                return true;
            }

            error_log(sprintf('WP Paddle Gateway Error - %s: %s', $response['error']['code'], $response['error']['message']));

            return new \WP_Error($response['error']['code'], $response['error']['message']);
        }

        protected function get_paddle_credentials() {
            $credentials = [];

            $entity = new Entities\Settings_Entity();

            $credentials['vendor_id'] = $entity->get('vendor_id');
            $credentials['auth_code'] = $entity->get('auth_code');

            return $credentials;
        }

        protected function get_test_mode() {
            $entity = new Entities\Settings_Entity();

            $test_mode = $entity->get('test_mode');

            return $test_mode;
        }

        public function get_paddle_api_url($relativePath) {
            $test_mode = $this->get_test_mode();
            if (!empty($test_mode)) {
                $domain = 'https://sandbox-vendors.paddle.com';
            } else {
                $domain = 'https://vendors.paddle.com';
            }
            return $domain . $relativePath;
        }

    }

}