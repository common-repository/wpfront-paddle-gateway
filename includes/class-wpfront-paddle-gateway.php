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

require __DIR__ . '/class-template-base.php';
require __DIR__ . '/api/class-paddle-api.php';
require __DIR__ . '/dashboard/class-dashboard.php';
require __DIR__ . '/settings/class-settings.php';
require __DIR__ . '/paylinks/class-paylink.php';
require __DIR__ . '/payments/class-payments.php';
require __DIR__ . '/webhook/class-webhook.php';
require __DIR__ . '/edd/class-edd-paddle.php';

if(file_exists(__DIR__ . '/pro/includes.php')) {
    require __DIR__ . '/pro/includes.php';
}

use WPFront\Paddle\Settings;
use WPFront\Paddle\EDD\EDD_Paddle;

if (!class_exists('\WPFront\Paddle\WPFront_Paddle_Gateway')) {

    /**
     * Paddle integration base class
     *
     * @author Syam Mohan <syam@wpfront.com>
     * @copyright 2021 WPFront.com
     */
    class WPFront_Paddle_Gateway {

        const VERSION = '1.1';
        const GATEWAY_KEY = 'wpfront-paddle-gateway';

        protected static $instance = null;
        protected $plugin_file;
        protected $plugin_url;
        protected $menu = array();
        
        private $menu_position = 0;

        protected function __construct() {
            add_action('admin_menu', array($this, 'admin_menu'));
        }

        /**
         * Returns class instance.
         *
         * @return WPFront_Paddle_Gateway
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new WPFront_Paddle_Gateway();
            }

            return self::$instance;
        }

        /**
         * Initialize method.
         */
        public function init($plugin_file) {
            $this->plugin_file = $plugin_file;
            $this->plugin_url = plugin_dir_url($plugin_file);

            if(class_exists('\WPFront\Paddle\Pro\License')) {
                \WPFront\Paddle\Pro\License::instance()->init($this);
                if(!\WPFront\Paddle\Pro\License::instance()->is_valid()) {
                    return;
                }
            }
            
            Dashboard::instance()->init($this);
            Payments::instance()->init($this);
            Paylink::instance()->init($this);
            Settings::instance()->init($this);
            Webhook::instance()->init($this);

            if ($this->is_integrate_edd()) { 
                EDD_Paddle::instance()->init($this);
            }
        }

        public function admin_menu() {
            ksort($this->menu);
            
            $parent_slug = '';
            foreach ($this->menu as $menu) {
                if (!current_user_can($menu->cap)) {
                    continue;
                }

                if (empty($parent_slug)) {
                    $parent_slug = $menu->menu_slug;
                    add_menu_page(__('Paddle', 'wpfront-paddle-gateway'), __('Paddle', 'wpfront-paddle-gateway'), $menu->cap, $parent_slug, '', 'dashicons-money-alt');
                }

                $hook_suffix = add_submenu_page($parent_slug, $menu->page_title, $menu->menu_title, $menu->cap, $menu->menu_slug, $menu->function, $menu->position);

                call_user_func_array($menu->callback, array($hook_suffix, $menu->menu_slug));
            }
        }

        public function add_admin_menu($page_title, $menu_title, $capability, $menu_slug, $function, $position, $callback) {
            $this->menu_position++;
            
            if(empty($position)) {
                $position = $this->menu_position;
            } else {
                $this->menu_position = $position;
            }
            
            $this->menu[$position] = (object) array(
                        'page_title' => $page_title,
                        'menu_title' => $menu_title,
                        'cap' => $capability,
                        'menu_slug' => self::GATEWAY_KEY . '-' . $menu_slug,
                        'function' => $function,
                        'position' => $position,
                        'callback' => $callback
            );
        }

        public function get_parent_menu_slug() {
            if (empty($this->menu)) {
                return null;
            }

            foreach ($this->menu as $menu) {
                if (!current_user_can($menu->cap)) {
                    continue;
                }

                return $menu->menu_slug;
            }

            return null;
        }

        public function get_plugin_file() {
            return $this->plugin_file;
        }

        public function get_asset_url($relativePath) {
            return $this->plugin_url . 'assets/' . $relativePath;
        }
        
        public function is_integrate_edd() {
            return !empty(Settings::instance()->get_setting('integrate-edd'));
        }

    }
    
}

