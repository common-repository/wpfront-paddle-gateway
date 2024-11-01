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

require __DIR__ . '/class-admin-payments-list-table.php';

if (!class_exists('\WPFront\Paddle\Admin\Admin_Payments_Table_View')) {

    class Admin_Payments_Table_View extends \WPFront\Paddle\Template_Base {

        /**
         *
         * @var Admin_Payments
         */
        protected $controller;

        public function __construct($controller) {
            $this->controller = $controller;
        }

        public function view() {
            ?>
            <div class="wrap paddle-admin-payments">
                <?php $this->title(); ?>
                <?php $this->filter_links(); ?>
                <?php
                $list_table = new Admin_Payments_List_Table($this->controller);
                $list_table->prepare_items();
                ?>
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr($this->controller->get_menu_slug()); ?>" />
                    <input type="hidden" name="list" value="<?php echo esc_attr($this->controller->get_active_list_filter()); ?>" />
                    <?php $list_table->search_box(__('Search', 'wpfront-paddle-gateway'), 'payments'); ?>
                </form>
                <form id="form-paddle-payments" method='post' type="submit" onsubmit="return paddle_payment_bulk_delete()" >
                    <?php
                    $list_table->display();
                    ?>
                </form >
                <?php $this->script(); ?>
            </div>
            <?php
        }

        public function title() {
            ?>
            <h2>
                <?php echo __('Payments', 'wpfront-paddle-gateway'); ?>         
            </h2>
            <?php
            $this->notices();
        }

        protected function filter_links() {
            $self_url = $this->controller->get_self_url();
            ?>
            <ul class="subsubsub">
                <li>
                    <?php
                    $link_data = array();
                    $active_filter = $this->controller->get_active_list_filter();
                    $filter_data = $this->controller->get_list_filter_data();
                    foreach ($filter_data as $key => $value) {
                        $url = add_query_arg('list', $key, $self_url);
                        $link_data[] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>', esc_url_raw($url), ($active_filter == $key ? 'current' : ''), esc_html($value['display']), esc_html($value['count']));
                    }
                    echo wp_kses(implode('&#160;|&#160;</li><li> ', $link_data), wp_kses_allowed_html('post'), ['http', 'https']);
                    ?>
                </li>
            </ul>
            <?php
        }

        protected function notices() {
            if (isset($_GET['payment-deleted'])) {
                ?>
                <div class="notice notice-success">
                    <p>
                        <?php echo __('Payment deleted successfully.', 'wpfront-paddle-gateway'); ?>
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
                    . __('This screen lists all the payments done through paddle in your site.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p>'
                ),
                array(
                    'id' => 'columns',
                    'title' => __('Columns', 'wpfront-paddle-gateway'),
                    'content' => '<p><strong>'
                    . __('Product Name', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Name of the product purchased.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Date', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Date and time when the purchase was done.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('User Account', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Name of the user (if logged in).', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Product Price', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Price for the product.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Customer Email', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Email with which the payment was done.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Customer Name', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Name of the customer who purchased the product.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Customer Gross', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Total amount paid by the customer.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Earnings', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Total amount earned by the seller.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Invoice', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Invoice receipt of the purchase.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Status', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Status of the purchase. It can be pending, completed, refunded or abandoned.', 'wpfront-paddle-gateway')
                    . '</p>'
                ),
                array(
                    'id' => 'actions',
                    'title' => __('Actions', 'wpfront-paddle-gateway'),
                    'content' => '<p>'
                    . __('Hovering over a row in the payments list will display action links that allow you to manage payments. You can perform the following actions:', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p><strong>'
                    . __('Delete', 'wpfront-paddle-gateway')
                    . '</strong>: '
                    . __('Allows you to delete that payment.', 'wpfront-paddle-gateway')
                    . '</p>'
                )
            );


            $sidebar = array(
                array(
                    __('Documentation on Payments', 'wpfront-paddle-gateway'),
                    'payments/'
                )
            );

            $this->set_help_tab($tabs, $sidebar);
        }

        public function script() {
            ?>
            <script type="text/javascript">
                function paddle_payment_bulk_delete() {
                    var x = document.getElementById("bulk-action-selector-top").value;
                    if (x === 'delete') {
                        return confirm(<?php echo wp_json_encode(__('Do you want to delete these payments?', 'wpfront-paddle-gateway')); ?>);
                    }
                }
            </script>
            <?php
        }

    }

}

