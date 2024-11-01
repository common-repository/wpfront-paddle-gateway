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

use WPFront\Paddle\Webhook;

if (!class_exists('\WPFront\Paddle\Admin\Admin_Settings_View')) {

    class Admin_Settings_View extends \WPFront\Paddle\Template_Base {

        /**
         *
         * @var Admin_Settings 
         */
        protected $controller;

        public function __construct($controller) {
            $this->controller = $controller;
        }

        public function view() {
            ?>
            <div class="wrap paddle-admin-settings">
                <?php $this->title(); ?>
                <form method="post" class="validate" action="<?php echo esc_attr($this->controller->get_self_url()); ?>">
                    <table class="form-table">
                        <tbody>
                            <?php $this->content(); ?>  
                        </tbody>                         
                    </table>
                    <?php $this->title_integrate(); ?>
                   <table class="form-table">
                        <tbody>
                            <?php $this->integrate_content(); ?>  
                        </tbody>                         
                    </table>
                    <?php
                    wp_nonce_field('wp-paddle-settings');
                    submit_button();
                    ?>
                </form>
            </div>   
            <?php
        }

        protected function title() {
            ?>
            <h2>
                <?php echo __('Paddle Settings', 'wpfront-paddle-gateway'); ?>
            </h2>
            <?php
            $this->notices();
        }

        protected function title_integrate() {
            ?>
            <h3>
                <?php echo __('Integrate', 'wp-paddle-gateway'); ?>
            </h3>
            <?php
        }

        protected function notices() {
            $error = $this->controller->get_error();
            if (!empty($error)) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php echo esc_html($error); ?>
                    </p>
                </div>
                <?php
            }

            if (isset($_GET['changes-saved'])) {
                ?>
                <div class="notice notice-success">
                    <p>
                        <?php echo __('Settings saved.', 'wpfront-paddle-gateway'); ?>
                    </p>
                </div>
                <?php
            }
        }

        protected function content() {
            $this->checkbox_row(
                    __('Test Mode', 'wpfront-paddle-gateway'),
                    'test_mode'
            );
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
        }

        protected function integrate_content() {
            $this->checkbox_row(
                    __('Easy Digital Downloads', 'wp-paddle-gateway'),
                    'integrate-edd'
            );
        }

        protected function textbox_row($label, $name) {
            $value = '';
            if (!empty($_POST['submit'])) {
                if (!empty($_POST[$name])) {
                    $value = sanitize_text_field($_POST[$name]);
                }
            } else {
                $value = $this->controller->get_setting($name);
                if ($value === null) {
                    $value = '';
                }
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

        protected function checkbox_row($label, $name) {
            $value = $this->controller->get_setting($name);
            if (!empty($value)) {
                $arg = 'checked';
            } else {
                $arg = '';
            }
            ?>          
            <tr>
                <th scope="row">
                    <?php echo esc_html($label); ?>
                </th>
                <td>
                    <input id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" type="checkbox" <?php echo esc_attr($arg); ?> />
                </td>
            </tr>
            <?php
        }

        protected function display_row($label, $name) {
            ?>
            <tr>
                <th scope="row">
                    <?php echo esc_html($label); ?>
                </th>
                <td>
                    <p><b> <?php echo esc_url(Webhook::instance()->get_webhook_url()); ?> </b></p>
                    <p class="description"><?php echo __('Please enter the above URL as webhook URL in your Paddle Alerts / Webhooks page.', 'wpfront-paddle-gateway'); ?></p>
                </td>
            </tr>
            <?php
        }

        protected function textarea_row($label, $name) {
            $value = '';
            if (!empty($_POST['submit'])) {
                if (!empty($_POST[$name])) {
                    $value = sanitize_textarea_field($_POST[$name]);
                }
            } else {
                $value = $this->controller->get_setting($name);
                if ($value === null) {
                    $value = '';
                }
            }
            ?>       
            <tr>
                <th scope="row">
                    <?php echo esc_html($label); ?>
                </th>
                <td>
                    <textarea id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name);?>" class="large-text" cols="50" rows="14"><?php echo esc_textarea($value); ?></textarea>
                </td>           
            </tr>
            <?php
        }

        public function set_help() {
            $tabs = array(
                array(
                    'id' => 'overview',
                    'title' => __('Overview', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('This screen allows you to edit the paddle payment settings.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p>'
                ),
                array(
                    'id' => 'testmode',
                    'title' => __('Test Mode', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Check for testing in sandbox account.', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'vendorid',
                    'title' => __('Vendor ID', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Enter the vendor ID of your Paddle account. The vendor ID identifies your seller account. This can be found in Developer Tools > Authentication.', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'authcode',
                    'title' => __('Auth Code', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Enter the auth code of your Paddle account. The vendor auth code is a private API key for authenticating API requests. This key should never be used in client side code or shared publicly. This can be found in Developer Tools > Authentication.', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'publickey',
                    'title' => __('Public Key', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Enter the public key of your Paddle account. Paddle send a signature field with each webhook that can be used to verify that the webhook was sent by Paddle. This can be found in Developer Tools > Public Key .', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'webhookurl',
                    'title' => __('Webhook Url', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Enter the above URL in your Paddle account to receive payment alerts. Webhooks are typically used to send order-specific data to a customer after a checkout is completed.', 'wpfront-paddle-gateway')
                    . '</p>'
                )
            );

            $sidebar = array(
                array(
                    __('Documentation on Settings', 'wpfront-paddle-gateway'),
                    'settings/'
                )
            );

            $this->set_help_tab($tabs, $sidebar);
        }

    }

}
    