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

require __DIR__ . '/class-paylink-entity.php';
require __DIR__ . '/class-admin-paylinks.php';

use WPFront\Paddle\Entities\Paylink_Entity;

if (!class_exists('\WPFront\Paddle\Paylink')) {

    class Paylink {

        protected static $instance = null;

        protected function __construct() {
            
        }

        /**
         * Returns class instance.
         *
         * @return Paylink
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new Paylink();
            }

            return self::$instance;
        }

        public function init($main) {
            new Admin\Admin_Paylinks($main);

            add_action('init', array($this, 'register_shortcodes'));
        }

        public function register_shortcodes() {
            $entity = new Paylink_Entity();
            $entities = $entity->get_all();

            if (empty($entities)) {
                return;
            }

            foreach ($entities as $entity) {
                add_shortcode($this->get_shortcode($entity), array($this, 'process_shortcode'));
            }

            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_style'));
            add_action('wp_ajax_wpfront_paddle_gateway_paylink', array($this, 'paylink_action'));
            add_action('wp_ajax_nopriv_wpfront_paddle_gateway_paylink', array($this, 'paylink_action'));
        }

        public function enqueue_scripts() {
            $js = 'js/paylinks.min.js';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $js = 'js/paylinks.js';
            }

            wp_enqueue_script('wpfront-paddle-gateway-paylink', WPFront_Paddle_Gateway::instance()->get_asset_url($js), array('jquery'), WPFront_Paddle_Gateway::VERSION, true);
            wp_localize_script('wpfront-paddle-gateway-paylink', 'wpfront_paddle_gateway_paylink_data', array(
                'ajaxurl' => admin_url('admin-ajax.php'), 
                'spinnerurl' => admin_url('images/spinner.gif')
            ));
        }
        
        public function enqueue_style() {
            $css = 'css/paylinks.min.css';
            if(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $css = 'css/paylinks.css';
            }
            
            wp_enqueue_style('wpfront-paddle-gateway-paylink', WPFront_Paddle_Gateway::instance()->get_asset_url($css), array(), WPFront_Paddle_Gateway::VERSION);
        }

        public function process_shortcode($atts, $content, $tag) { 
            $id = $this->extract_id($tag);
            if (empty($id)) {
                return $content;
            }

            $entity = new Paylink_Entity();
            $entity = $entity->get($id);

            if (empty($entity)) {
                return '';
            }

            $data = '';

            if (!empty($entity->allow_atts)) {
                $atts = shortcode_atts(array(
                    'label' => $entity->label,
                    'price' => '',
                    'title' => ''
                        ), $atts);


                if (!empty($atts['price'])) {
                    $data .= "data-price='{$atts['price']}' ";
                }

                if (!empty($atts['title'])) {
                    $data .= "data-title='{$atts['title']}' ";
                }

                $label = esc_html($atts['label']);
            } else {
                $label = $entity->label;
            }
            
            $nonce = wp_create_nonce('wpfront-paddle-gateway-paylink');
            $loading = __('Loading', 'wpfront-paddle-gateway');
            return "<a class='button wpfront-paddle-gateway-paylink paylink-button' rel='nofollow' href='#!' data-nonce='{$nonce}' data-id='{$entity->id}' {$data}>"
                    . "<span class= 'wpfront-paddle-gateway-label'>{$label}</span>"
                    . "<span class='wpfront-paddle-gateway-spinner' aria-label={$loading}></span>" 
                    . "</a>";
        }

        public function paylink_action() { 
            $id = intval($_POST['id']);

            $entity = new Paylink_Entity();
            $paylink_entity = $entity->get($id);

            if (empty($paylink_entity)) {
                echo json_encode(['url' => $_SERVER['HTTP_REFERER']]);
                die();
            }
            
            if(!wp_verify_nonce($_POST['nonce'], 'wpfront-paddle-gateway-paylink')) {
                echo json_encode(['url' => $_SERVER['HTTP_REFERER']]);
                die();
            }

            $price = $paylink_entity->price;
            $title = $paylink_entity->title;
            if (!empty($paylink_entity->allow_atts)) {
                if (!empty($_POST['price'])) {
                    $price = floatval($_POST['price']);
                }

                if (!empty($_POST['title'])) {
                    $title = sanitize_text_field($_POST['title']);
                }
            }

            $payload = [
                'title' => $title,
                'currency' => 'USD',
                'price' => $price,
                'return_url' => $_SERVER['HTTP_REFERER']
            ];

            $payload = apply_filters('paddle_generate_paylink_payload', $payload, $paylink_entity);

            $url = Paddle_API::instance()->get_pay_link($payload);
            if (empty($url) || is_wp_error($url)) {
                $url = $_SERVER['HTTP_REFERER'];
            }

            echo json_encode(['url' => $url]);
            die();
        }

        public function get_shortcode($entity, $format = false) {
            $s = "paddle-paylink-{$entity->id}";
            if ($format) {
                $s = "[$s]";
            }

            return $s;
        }

        protected function extract_id($tag) {
            if (substr($tag, 0, 15) !== 'paddle-paylink-') {
                return 0;
            }

            return intval(substr($tag, 15));
        }

    }

}