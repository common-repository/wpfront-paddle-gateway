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
}

if (!class_exists('\WPFront\Paddle\Admin\Admin_Paylinks_List_Table')) {

    class Admin_Paylinks_List_Table extends \WP_List_Table {

        /**
         *
         * @var Admin_Paylinks
         */
        protected $controller;

        public function __construct($controller) {
            $this->controller = $controller;
            $this->set_help_tab();
            parent::__construct(array('screen' => 'paddle'));
        }

        function prepare_items() {
            $search = '';
            if (!empty($_GET['s'])) {
                $search = sanitize_text_field($_GET['s']);
            }

            $this->items = $this->controller->search($search);

            $this->set_pagination_args(array(
                'total_items' => count($this->items),
                'per_page' => PHP_INT_MAX,
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
                'name' => __('Name', 'wpfront-paddle-gateway'),
                'label' => __('Link Label', 'wpfront-paddle-gateway'),
                'title' => __('Product Title', 'wpfront-paddle-gateway'),
                'price' => __('Price', 'wpfront-paddle-gateway'),
                'shortcode' => __('Shortcode', 'wpfront-paddle-gateway')
            );

            return $columns;
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

                            case 'name':
                                $this->name_cell($item, $attributes);
                                break;

                            case 'label':
                                $this->label_cell($item);
                                break;

                            case 'title':
                                $this->title_cell($item);
                                break;

                            case 'price':
                                $this->price_cell($item);
                                break;

                            case 'shortcode':
                                $this->shortcode_cell($item);
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
                <label class="screen-reader-text" for="paylink_select" ?></label>
                <input type="checkbox" id="<?php echo 'paylinks_' . esc_attr($item->id); ?>" name="paylinks[]" value="<?php echo esc_attr($item->id); ?>" />
            </th>
            <?php
        }

        protected function name_cell($item, $attributes) {
            ?>
            <td <?php echo esc_attr($attributes); ?>>
                <?php
                $edit_link = $this->controller->get_edit_url($item->id);
                ?>
                <strong>
                    <a href="<?php echo esc_attr($edit_link); ?>" class="edit">
                        <?php echo esc_html($item->name); ?>
                    </a>
                </strong>              
                <?php
                $actions = array();
                $edit_link = esc_attr($this->controller->get_edit_url($item->id));
                $display = __('Edit', 'wpfront-paddle-gateway');
                $actions['edit'] = "<a href='$edit_link'>$display</a>";

                $delete_link = esc_attr($this->controller->get_delete_url($item->id));
                $display = __('Delete', 'wpfront-paddle-gateway');
                $actions['delete'] = "<a href='$delete_link'>$display</a>";


                echo $this->row_actions($actions);
                ?> 
            </td>
            <?php
        }

        protected function label_cell($item) {
            ?>
            <td class="label column-label">
                <?php echo esc_html($item->label); ?>
            </td> 
            <?php
        }

        protected function title_cell($item) {
            ?>
            <td class="title column-title">
                <?php echo esc_html($item->title); ?>
            </td>
            <?php
        }

        protected function price_cell($item) {
            ?>
            <td class="price column-price">
                <?php echo esc_html($item->price); ?>
            </td> 
            <?php
        }

        protected function shortcode_cell($item) {
            ?>
            <td class="shortcode column-shortcode">
                <input type="text" readonly="true" value="<?php echo esc_attr($this->controller->get_shortcode($item)); ?>" />
            </td> 
            <?php
        }
      
    }

}

