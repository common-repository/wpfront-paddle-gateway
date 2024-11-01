<?php

/*
  WPFront Paddle Gateway Plugin
  Copyright (C) 2021, WPFront.com
  Website: wpfront.com
  Contact: syam@wpfront.com

  WPFront Paddle Gateway Plugin Plugin is distributed under the GNU General Public License, Version 3,
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

if (!class_exists('\WPFront\Paddle\Webhook')) {

    class Webhook {

        protected static $instance = null;

        protected function __construct() {
            
        }

        /**
         * Returns class instance.
         *
         * @return Webhook
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new Webhook();
            }

            return self::$instance;
        }

        public function init($main) {
            add_action('init', array($this, 'process_request'));
        }

        public function process_request() {
            if (empty($_GET['paddle-listener']) || $_GET['paddle-listener'] !== WPFront_Paddle_Gateway::GATEWAY_KEY) {
                return;
            }

            if (!$this->verify_signature()) {
                exit('Signature failure.');
            }

            if (empty($_POST['alert_name'])) {
                exit('"alert_name" missing.');
            }

            $alert_name = sanitize_text_field($_POST['alert_name']);

            do_action("paddle_{$alert_name}");
            exit('OK');
        }

        public function get_webhook_url() {
            return defined('PADDLE_WEBHOOK_URL') ? PADDLE_WEBHOOK_URL : add_query_arg(['paddle-listener' => WPFront_Paddle_Gateway::GATEWAY_KEY], home_url());
        }

        /**
         * Validation method to verify webhook call parameters.
         * Verifies the parameters using OpenSSL signature and public key.
         * Signature verified data is trustable since only Paddle knows the private key.
         * 
         * @return boolean
         */
        protected function verify_signature() {
            /*
             * https://developer.paddle.com/webhook-reference/verifying-webhooks
             */

            $entity = new Entities\Settings_Entity();
            $public_key = $entity->get('public_key');

            if (empty($public_key)) {
                return false;
            }

            if (empty($_POST['p_signature'])) {
                return false;
            }

            //$public_key = openssl_get_publickey($public_key);
            // Get the p_signature parameter & base64 decode it.
            $signature = base64_decode(sanitize_text_field($_POST['p_signature']));

            // Get the fields sent in the request, and remove the p_signature parameter
            $fields = $_POST;
            unset($fields['p_signature']);

            if (isset($fields['customer_name'])) {
                $fields['customer_name'] = stripcslashes($fields['customer_name']);
            }

            // ksort() and serialize the fields
            ksort($fields);
            foreach ($fields as $k => $v) {
                if (!in_array(gettype($v), array('object', 'array'))) {
                    $fields[$k] = sanitize_text_field($v);
                }
            }

            $data = serialize($fields);

            $verification = openssl_verify($data, $signature, $public_key, OPENSSL_ALGO_SHA1);

            if ($verification === 1) {
                unset($_POST['p_signature']);
                return true;
            }

            return false;
        }

    }

}

