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

namespace WPFront\Paddle\Admin;

if (!defined('ABSPATH')) {
    exit();
}

use \WPFront\Paddle\Settings;
use WPFront\Paddle\WPFront_Paddle_Gateway;

require __DIR__ . '/template-admin-settings.php';

if (!class_exists('\WPFront\Paddle\Admin\Admin_Settings')) {

    class Admin_Settings {

        protected $cap = 'manage_options';
        protected $main;
        protected $menu_slug;
        protected $error;
        protected $view;
        protected $prefix;

        public function __construct($main) {
            $this->main = $main;

            $this->main->add_admin_menu(
                    __('Paddle Settings', 'wpfront-paddle-gateway'), 
                    __('Settings', 'wpfront-paddle-gateway'), 
                    $this->get_cap(), 
                    'settings', 
                    array($this, 'view'),
                    null,
                    array($this, 'menu_callback')
            );
        }

        public function menu_callback($hook_suffix, $menu_slug) {
            $this->menu_slug = $menu_slug;
            $this->prefix = WPFront_Paddle_Gateway::GATEWAY_KEY;
            add_action("load-$hook_suffix", array($this, 'load_view'));
        }

        public function load_view() {


            if (!current_user_can($this->get_cap())) {
                wp_die(__('You are not allowed to view this page.', 'wpfront-paddle-gateway'));
            }

            $this->view = new Admin_Settings_View($this);
            $this->view->set_help();

            if (!empty($_POST['submit'])) {
                check_admin_referer('wp-paddle-settings');
                
                $vendor_id = sanitize_text_field($_POST['vendor_id']);
                if(empty($vendor_id)) {
                    $this->error = __('Vendor ID is required.', 'wpfront-paddle-gateway');
                    return;
                }
                
                $auth_code = sanitize_text_field($_POST['auth_code']);
                if(empty($auth_code)) {
                    $this->error = __('Auth Code is required.', 'wpfront-paddle-gateway');
                    return;
                }
                
                $public_key = sanitize_textarea_field($_POST['public_key']);
                if(empty($public_key)) {
                    $this->error = __('Public Key is required.', 'wpfront-paddle-gateway');
                    return;
                }

                $this->update_setting('test_mode', !empty($_POST['test_mode']));
                $this->update_setting('vendor_id', $vendor_id);
                $this->update_setting('auth_code', $auth_code);
                $this->update_setting('public_key', $public_key);
                $this->update_setting('integrate-edd', !empty($_POST['integrate-edd']));
                               
                wp_redirect($this->get_self_url(['changes-saved' => 'true']));
                exit;
            }
        }

        public function get_error() {
            if (empty($this->error)) {
                return null;
            }

            return $this->error;
        }

        public function view() {
            $this->view->view();
        }

        public function get_cap() {
            return $this->cap;
        }

        public function get_self_url($args = []) {
            return add_query_arg($args, menu_page_url($this->menu_slug, false));
        }

        public function update_setting($name, $value) {
            Settings::instance()->update_setting($name, $value);
        }

        public function get_setting($name) {
            return Settings::instance()->get_setting($name);
        }

    }

}