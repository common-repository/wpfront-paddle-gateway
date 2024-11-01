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

require __DIR__ . '/class-admin-paylinks-list-table.php';

if (!class_exists('\WPFront\Paddle\Admin\Admin_Paylinks_Table_View')) {

    class Admin_Paylinks_Table_View extends \WPFront\Paddle\Template_Base{

        /**
         *
         * @var Admin_Paylinks
         */
        protected $controller;

        public function __construct($controller) {
            $this->controller = $controller;
        }

        public function view() {
            ?>
            <div class="wrap paddle-admin-paylinks">
                <?php $this->title(); ?>  
                <?php
                $list_table = new Admin_Paylinks_List_Table($this->controller);
                $list_table->prepare_items();
                ?>
                <form action="" method="get" class="search-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($this->controller->get_menu_slug()); ?>" />
                    <?php $list_table->search_box(__('Search', 'wpfront-paddle-gateway'), 'paddle'); ?>
                </form>
                <form id="form-paddle-paylinks" method='post'>
                    <?php
                    $list_table->display();
                    ?>
                </form>
            </div> 
            <?php
        }

        public function title() {
            ?>
            <h2>
                <?php echo __('Paylinks', 'wpfront-paddle-gateway'); ?>         
                <a href="<?php echo esc_attr($this->controller->get_add_new_url()); ?>" class="add-new-h2"><?php echo __('Add New', 'wpfront-paddle-gateway'); ?></a>
            </h2>
            <?php
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

            if (isset($_GET['paylink-deleted'])) {
                ?>
                <div class="notice notice-success">
                    <p>
                        <?php echo __('Paylink(s) deleted successfully.', 'wpfront-paddle-gateway'); ?>
                    </p>
                </div>
                <?php
            }
        }
        
         public function set_help() {            
            $tabs = array(
                array(
                    'id' => 'overview',
                    'title' => __('Overview', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('This screen lists all the existing paylinks within your site.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p>'
                    . __('To add a new paylink, click the Add New button at the top of the screen.', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'columns',
                    'title' => __('Columns', 'wpfront-paddle-gateway'),
                    'content' => '<p><strong>'
                    . __('Name', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Used to identify the paylink.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Link Label', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Used as the label for the link in the site.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Product Title', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Title for the product.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Price', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Price for the product.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Shortcode', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Shortcode for the paylink that is to be added to the site.', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'actions',
                    'title' => __('Actions', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Hovering over a row in the paylinks list will display action links that allow you to manage paylinks. You can perform the following actions:', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Edit', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Allows you to edit that paylink.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Delete', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Allows you to delete that paylink.', 'wpfront-paddle-gateway')
                    . '</p>'
                )
            );


            $sidebar = array(
                array(
                    __('Documentation on Paylinks', 'wpfront-paddle-gateway'),
                    'paylinks/'
                )
            );

            $this->set_help_tab($tabs, $sidebar);
        }

    }

}