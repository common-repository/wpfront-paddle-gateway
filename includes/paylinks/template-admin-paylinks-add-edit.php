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

if (!class_exists('\WPFront\Paddle\Admin\Admin_Paylinks_Add_Edit_View')) {

    class Admin_Paylinks_Add_Edit_View extends \WPFront\Paddle\Template_Base {

        /**
         *
         * @var Admin_Paylinks
         */
        protected $controller;
        protected $paylink_data;

        public function __construct($controller, $data = null) {
            $this->controller = $controller;
            $this->paylink_data = $data;
        }

        public function view() {
            ?>
            <div class="wrap paddle-admin-paylinks">
                <?php $this->title(); ?>   
                <?php
                if (empty($this->paylink_data)) {
                    $action = $this->controller->get_add_new_url();
                } else {
                    $action = $this->controller->get_edit_url($this->paylink_data->id);
                }
                ?>
                <form method="post" class="validate" action="<?php echo esc_attr($action); ?>">
                    <table class="form-table">
                        <tbody>
                            <?php $this->content(); ?>  
                        </tbody>                      
                    </table>
                    <?php
                    wp_nonce_field('wp-paddle-paylinks');
                    submit_button();
                    ?>
                </form>
            </div> 
            <?php
        }

        public function title() {
            if (empty($this->paylink_data)) {
                ?>
                <h2>
                    <?php echo __('Add Paylink', 'wpfront-paddle-gateway'); ?>         
                </h2>
                <?php
            } else {
                ?>
                <h2>
                    <?php echo __('Edit Paylink', 'wpfront-paddle-gateway'); ?>         
                </h2>
                <?php
            }
            $this->notices();
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

            if (isset($_GET['paylink-added'])) {
                ?>
                <div class="notice notice-success">
                    <p>
                        <?php echo __('Paylink added successfully.', 'wpfront-paddle-gateway'); ?>
                    </p>
                </div>
                <?php
            }

            if (isset($_GET['paylink-updated'])) {
                ?>
                <div class="notice notice-success">
                    <p>
                        <?php echo __('Paylink updated successfully.', 'wpfront-paddle-gateway'); ?>
                    </p>
                </div>
                <?php
            }
        }

        public function content() {
            $this->textbox_row(
                    __('Name', 'wpfront-paddle-gateway'),
                    'name'
            );
            $this->textbox_row(
                    __('Link Label', 'wpfront-paddle-gateway'),
                    'label'
            );
            $this->textbox_row(
                    __('Product Title', 'wpfront-paddle-gateway'),
                    'title'
            );
            $this->textbox_row(
                    __('Price', 'wpfront-paddle-gateway'),
                    'price'
            );
            $this->checkbox_row(
                    __('Allow Shortcode Attributes', 'wpfront-paddle-gateway'),
                    'allow_atts'
            );
        }

        protected function textbox_row($label, $name) {
            $value = '';
            if (!empty($_POST['submit'])) {
                if (!empty($_POST[$name])) {
                    $value = sanitize_text_field($_POST[$name]);
                }
            }
            if (!empty($this->paylink_data)) {
                if ($name == 'price') {
                    if (is_numeric($this->paylink_data->$name)) {
                        $value = $this->paylink_data->$name;
                    }
                } else {
                    if (!empty($this->paylink_data->$name)) {
                        $value = $this->paylink_data->$name;
                    }
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
            $arg = '';
            if (!empty($_GET['id'])) {
                $paylink = $this->controller->get_paylinks_data(intval($_GET['id']));
                if (!empty($paylink->allow_atts)) {
                    $arg = 'checked';
                }
            }
            ?>          
            <tr>
                <th scope="row">
                    <?php echo esc_html($label); ?>
                </th>
                <td>
                    <input id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" type="checkbox" <?php echo esc_attr($arg); ?>/>
                </td>
            </tr>
            <?php
        }

        public function set_help() {
            if ($this->paylink_data !== null) {
                $tabs = array(
                    array(
                        'id' => 'overview',
                        'title' => __('Overview', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('This screen allows you to edit paylinks.', 'wpfront-paddle-gateway')
                        . '</p>'
                        . '<p>'
                    ),
                    array(
                        'id' => 'name',
                        'title' => __('Name', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Edit the name for the paylink. Name is used for identifying the paylink.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'linklabel',
                        'title' => __('Link Label', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Edit the label for the link to be displayed. This label is displayed as the link.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'producttitle',
                        'title' => __('Product Title', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Edit the title for the product. Title for the product to be sold.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'price',
                        'title' => __('Price', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Edit the price for the product. This is the price to be paid by the user for the product.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'allowshortcodeattributes',
                        'title' => __('Allow Shortcode Attributes', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Check to add label, price and title as attributes in the shortcode. Attributes can be added by filling the above text boxes or passed along with the shortcode. If there are attributes passed through shortcode the above data in the text boxes will be overwritten by the passed values.', 'wpfront-paddle-gateway')
                        . '</p>'
                    )
                );

                $sidebar = array(
                    array(
                        __('Documentation on Edit Paylinks', 'wpfront-paddle-gateway'),
                        'edit-paylinks/'
                    )
                );
            } else {
                $tabs = array(
                    array(
                        'id' => 'overview',
                        'title' => __('Overview', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('This screen allows you to add new paylinks.', 'wpfront-paddle-gateway')
                        . '</p>'
                        . '<p>'
                    ),
                    array(
                        'id' => 'name',
                        'title' => __('Name', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Add a name for the paylink. Name is used for identifying the paylink.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'linklabel',
                        'title' => __('Link Label', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Add a label for the link to be displayed. This label is displayed as the link.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'producttitle',
                        'title' => __('Product Title', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Add a title for the product. Title for the product to be sold.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'price',
                        'title' => __('Price', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Add the price for the product. This is the price to be paid by the user for the product.', 'wpfront-paddle-gateway')
                        . '</p>'
                    ),
                    array(
                        'id' => 'allowshortcodeattributes',
                        'title' => __('Allow Shortcode Attributes', 'wpfront-paddle-gateway'),
                        'content' => '<p>'
                        . __('Check to add label, price and title as attributes in the shortcode. Attributes can be added by filling the above text boxes or passed along with the shortcode. If there are attributes passed through shortcode the above data in the text boxes will be overwritten by the passed values.', 'wpfront-paddle-gateway')
                        . '</p>'
                    )
                );

                $sidebar = array(
                    array(
                        __('Documentation on Add Paylinks', 'wpfront-paddle-gateway'),
                        'paylinks/'
                    )
                );
            }
            $this->set_help_tab($tabs, $sidebar);
        }

    }

}

