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

use WPFront\Paddle\Webhook;
use WPFront\Paddle\Entities\Settings_Entity;

if (!class_exists('\WPFront\Paddle\EDD\EDD_Settings_View')) {

    class EDD_Settings_View {

        private $data;
        
        public function __construct() {           
        }

        public function view($data) {
            $this->data = $data;
            ?>
                <tbody>
                    <?php $this->content(); ?>                     
                </tbody>                  
            <?php 
            wp_nonce_field('wp-edd-paddle-settings', 'wp_edd_paddle_nonce');
        }

        protected function content() {

            $this->textbox_row(
                    __('Vendor ID', 'wpfront-paddle-gateway'),
                    'vendor_id'
            );

            $this->textbox_row(
                    __('Auth Code', 'wpfront-paddle-gateway'),
                    'auth_code'
            );

            $this->textarea_row(
                    __('Public Key', 'wpfront-paddle-gateway'),
                    'public_key'
            );
            $this->display_row(
                    __('Webhook Url', 'wpfront-paddle-gateway'),
                    'webhook_url'
            );
            $this->textbox_row(
                    __('Checkout Label', 'wpfront-paddle-gateway'),
                    'checkout_label'
            );
        }

        protected function textbox_row($label, $name) {
            $value = '';
            if (!empty($_POST['submit'])) {
                if (!empty($_POST[$name])) {
                    $value = sanitize_text_field($_POST[$name]);
                }
            } elseif(isset($this->data[$name])) {
                $value = $this->data[$name];
            }
            ?>       
            <tr>
                <th scope="row">
                    <?php echo esc_html($label); ?>
                </th>
                <td>
                    <input id="<?php echo esc_attr($name); ?>" class="regular-text" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" type="text" />  
                </td>           
            </tr>
            <?php
        }

        protected function textarea_row($label, $name) {
            $value = '';
            if (!empty($_POST['submit'])) {
                if (!empty($_POST[$name])) {
                    $value = sanitize_text_area($_POST[$name]);
                }
            } elseif(isset($this->data[$name])) {
                $value = $this->data[$name];
            }         
            ?>       
            <tr>
                <th scope="row">
                    <?php echo esc_html($label); ?>
                </th>
                <td>
                    <textarea id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" class="large-text" cols="50" rows="14"><?php echo esc_html($value); ?></textarea>
                </td>           
            </tr>
            <?php
        }

        protected function display_row($label, $name) {
            ?>
            <tr>
                <th scope="row">
                    <?php echo $label; ?>
                </th>
                <td>
                    <p><b> <?php echo esc_url(Webhook::instance()->get_webhook_url()); ?> </b></p>
                    <p class="description"><?php echo __('Please enter the above URL as webhook URL in your Paddle Alerts / Webhooks page.', 'wpfront-paddle-gateway'); ?></p>
                </td>
            </tr>
            <?php
        }

    }

}
