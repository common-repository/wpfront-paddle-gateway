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

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    require __DIR__ . '/class-payments.php';
}

use WPFront\Paddle\Payments; 

if (!class_exists('\WPFront\Paddle\Admin\Admin_Payments_List_Table')) {

    class Admin_Payments_List_Table extends \WP_List_Table {

        /**
         *
         * @var Admin_Payments
         */
        protected $controller;

        public function __construct($controller) {
            $this->controller = $controller;
            parent::__construct(array('screen' => 'paddle'));
        }

        function prepare_items() {
            $search = '';
            if (!empty($_GET['s'])) {
                $search = sanitize_text_field($_GET['s']);
            }

            $orderby = 'id';
            if (!empty($_GET['orderby'])) {
                $orderby = sanitize_text_field($_GET['orderby']);
            }

            $order = 'desc';
            if (!empty($_GET['order'])) {
                $order = sanitize_text_field($_GET['order']);
            }

            $this->items = $this->controller->get_items($this->get_pagenum(), 20, $orderby, $order, $search);

            $this->set_pagination_args(array(
                'total_items' => $this->controller->get_items_count($search),
                'per_page' => 20,
            ));
        }

        function get_bulk_actions() {
            $actions = array();

            $actions['delete'] = __('Delete', 'wpfront-paddle-gateway');

            return $actions;
        }

        function get_columns() {
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'product_name' => __('Product Name', 'wpfront-paddle-gateway'),
                'date' => __('Date', 'wpfront-paddle-gateway'),
                'user' => __('User Account', 'wpfront-paddle-gateway'),
                'product_price' => __('Product Price', 'wpfront-paddle-gateway'),
                'email' => __('Customer Email', 'wpfront-paddle-gateway'),
                'customer_name' => __('Customer Name', 'wpfront-paddle-gateway'),
                'gross_amount' => __('Customer Gross', 'wpfront-paddle-gateway'),
                'earnings' => __('Earnings', 'wpfront-paddle-gateway'),
                'invoice' => __('Invoice', 'wpfront-paddle-gateway'),
                'status' => __('Status', 'wpfront-paddle-gateway')
            );

            return $columns;
        }

        function get_sortable_columns() {
            $sortable_columns = array(
                'product_name' => array('product_name', false),
                'date' => array('date', false),
                'email' => array('email', false)
            );
            return $sortable_columns;
        }

        function display_rows() {
            foreach ($this->items as $item) {
                $alt = empty($alt) ? 'alternate' : '';
                ?>
                <tr class="<?php echo esc_attr($alt); ?>">
                    <?php
                    list( $columns, $hidden ) = $this->get_column_info();

                    foreach ($columns as $column_name => $column_display_name) {
                        $class = "class='$column_name column-$column_name'";

                        $style = '';
                        if (in_array($column_name, $hidden)) {
                            $style = ' style="display:none;"';
                        }

                        $attributes = "$class$style";

                        switch ($column_name) {
                            case 'cb':
                                $this->cb_cell($item);
                                break;

                            case 'product_name':
                                $this->product_name_cell($item, $attributes);
                                break;

                            case 'date':
                                $this->date_cell($item);
                                break;

                            case 'user':
                                $this->user_cell($item);
                                break;

                            case 'product_price':
                                $this->product_price_cell($item);
                                break;

                            case 'email':
                                $this->customer_email_cell($item);
                                break;

                            case 'customer_name':
                                $this->customer_name_cell($item);
                                break;

                            case 'gross_amount':
                                $this->gross_amount_cell($item);
                                break;

                            case 'earnings':
                                $this->earnings_cell($item);
                                break;

                            case 'invoice':
                                $this->invoice_cell($item);
                                break;

                            case 'status':
                                $this->status_cell($item);
                                break;
                        }
                    }
                    ?>
                </tr>
                <?php
            }
        }

        protected function cb_cell($item) {
            ?>
            <th scope="row" class="check-column">
                <label class="screen-reader-text" for="payment_select" ?></label>
                <input type="checkbox" id="<?php echo 'payments_' . esc_attr($item->id); ?>" name="payments[]" value="<?php echo esc_attr($item->id); ?>" />
            </th>
            <?php
        }

        protected function product_name_cell($item, $attributes) {
            ?>
            <td <?php echo esc_attr($attributes); ?>>
                <?php echo esc_html($item->product_name); ?>
                <?php
                $delete_link = esc_attr($this->controller->get_delete_url($item->id));
                $display = __('Delete', 'wpfront-paddle-gateway');
                $actions['delete'] = "<a href='$delete_link' onclick='return paddle_payment_delete();' type='submit'>$display</a>";

                echo $this->row_actions($actions);
                ?>
            </td>
            <?php
            $this->script();
        }

        protected function date_cell($item) {
            ?>
            <td class="date column-date">
                <?php echo esc_html($item->date); ?>
            </td> 
            <?php
        }

        protected function user_cell($item) {
            if (!empty($item->user_id)) {
                $user_data = get_userdata($item->user_id);
                $display_name = $user_data->display_name;
                $user_link = get_edit_user_link($item->user_id);
            } else {
                $display_name = '';
                $user_link = '';
            }
            ?>
            <td>
                <strong>
                    <a href="<?php echo esc_url($user_link); ?>" class="edit">
                        <?php echo esc_html($display_name); ?>
                    </a>
                </strong> 
            </td> 
            <?php
        }

        protected function product_price_cell($item) {
            $asking_price = number_format($item->asking_price,2)
            ?>
            
            <td class="customer-email column-customer-email">
                <?php echo esc_html($item->currency . ' ' . $asking_price); ?>
            </td> 
            <?php
        }

        protected function customer_email_cell($item) {
            ?>
            <td class="customer-email column-customer-email">
                <?php echo esc_html($item->email); ?>
            </td> 
            <?php
        }

        protected function customer_name_cell($item) {
            ?>
            <td class="customer-name column-customer-name">
                <?php echo esc_html($item->customer_name); ?>
            </td> 
            <?php
        }

        protected function gross_amount_cell($item) {
            $customer_gross = number_format($item->sale_gross, 2);
            ?>
            <td class="gross-amount column-gross-amount">
                <?php
                if (!empty($item->sale_gross) && $item->status !== Payments::STATUS_REFUNDED) {
                    echo esc_html($item->currency . ' ' . $customer_gross);
                }
                ?>
            </td> 
            <?php
        }

        protected function earnings_cell($item) {
            $earnings = number_format($item->balance_earnings, 2);
            ?>
            <td class="earnings column-earnings">
            <?php
                if (!empty($item->balance_earnings) && $item->status !== Payments::STATUS_REFUNDED) {
                    echo esc_html($item->currency . ' ' . $earnings);
                }
                ?>
            </td> 
            <?php
        }

        protected function invoice_cell($item) {
            ?>
            <td class="earnings column-earnings">
                <?php
                if (!empty($item->receipt_url)) {
                    $receipt_url = $item->receipt_url;
                    ?>
                    <a target="_blank" href="<?php echo esc_url_raw($receipt_url); ?>">View</a>           
                </td> 
                <?php
            }
        }

        protected function status_cell($item) {
            ?>
            <td class="status column-status">
                <?php
                switch ($item->status) {
                    case 1:
                        echo __('Pending', 'wpfront-paddle-gateway');
                        break;

                    case 2:
                        echo __('Completed', 'wpfront-paddle-gateway');
                        break;

                    case 3:
                        echo __('Refunded', 'wpfront-paddle-gateway');
                        break;

                    case 4:
                        echo __('Abandoned', 'wpfront-paddle-gateway');
                        break;
                }
                ?>
            </td> 
            <?php
        }

        public function script() {
            ?>
            <script type="text/javascript">
                function paddle_payment_delete() {
                    return confirm(<?php echo wp_json_encode(__('Do you want to delete this payment?', 'wpfront-paddle-gateway')); ?>);
                }
            </script>
            <?php
        }

    }

}