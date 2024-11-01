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

require __DIR__ . '/template-edd-settings.php';

use WPFront\Paddle\EDD\EDD_Paddle;
use WPFront\Paddle\Settings;

if (!class_exists('\WPFront\Paddle\EDD\EDD_Settings')) {

    class EDD_Settings {

        protected $view;
        protected $error;
        protected static $instance = null;

        public function __construct() {
            add_filter('edd_settings_sections_gateways', array($this, 'settings_section'), 9999);
            add_action('edd_settings_tab_top_gateways_' . EDD_Paddle::GATEWAY_KEY, array($this, 'settings_fields'));
            add_action('admin_init', array($this, 'admin_init'));
        }

        /**
         * Returns class instance.
         *
         * @return EDD_Settings
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new EDD_Settings();
            }

            return self::$instance;
        }

        public function settings_section($sections) {
            $sections[EDD_Paddle::GATEWAY_KEY] = __('Paddle', 'wpfront-paddle-gateway');

            return $sections;
        }

        public function settings_fields($settings) {
            $data = array();
            $data['vendor_id'] = $this->get_vendor_id();
            $data['auth_code'] = $this->get_auth_code();
            $data['public_key'] = $this->get_public_key();
            $data['checkout_label'] = $this->get_checkout_label();

            $this->view = new EDD_Settings_View($this);
            $this->view->view($data);
        }

        public function admin_init() {
            if (empty($_POST['wp_edd_paddle_nonce'])) {
                return;
            }

            if (wp_verify_nonce($_POST['wp_edd_paddle_nonce'], 'wp-edd-paddle-settings')) {
                if (!empty($_POST['vendor_id'])) {
                    Settings::instance()->update_setting('vendor_id', sanitize_text_field($_POST['vendor_id']));
                }
                if (!empty($_POST['auth_code'])) {
                    Settings::instance()->update_setting('auth_code', sanitize_text_field($_POST['auth_code']));
                }
                if (!empty($_POST['public_key'])) {
                    Settings::instance()->update_setting('public_key', sanitize_textarea_field($_POST['public_key']));
                }
                Settings::instance()->update_setting('checkout_label', sanitize_text_field($_POST['checkout_label']));
            }
        }

        public function get_vendor_id() {
            return Settings::instance()->get_setting('vendor_id');
        }

        public function get_auth_code() {
            return Settings::instance()->get_setting('auth_code');
        }

        public function get_public_key() {
            return Settings::instance()->get_setting('public_key');
        }

        public function get_checkout_label() {
            return Settings::instance()->get_setting('checkout_label');
        }

    }

}

