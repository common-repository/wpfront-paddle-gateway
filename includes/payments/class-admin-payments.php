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

require __DIR__ . '/class-payments-entity.php';
require __DIR__ . '/template-admin-payments-list.php';

use WPFront\Paddle\Entities\Payments_Entity;
use WPFront\Paddle\Payments;

if (!class_exists('\WPFront\Paddle\Admin\Admin_Payments')) {

    class Admin_Payments {

        protected $cap = 'manage_options';
        protected $main;
        protected $objView = null;

        public function __construct($main) {
            $this->main = $main;

            $this->main->add_admin_menu(
                    __('Paddle Payments', 'wpfront-paddle-gateway'),
                    __('Payments', 'wpfront-paddle-gateway'),
                    $this->get_cap(),
                    'payments',
                    array($this, 'view'),
                    null,
                    array($this, 'menu_callback')
            );
        }

        public function menu_callback($hook_suffix, $menu_slug) {
            $this->menu_slug = $menu_slug;

            add_action("load-$hook_suffix", array($this, 'load_view'));
        }

        public function load_view() {
            if (!current_user_can($this->get_cap())) {
                wp_die(__('You are not allowed to view this page.', 'wpfront-paddle-gateway'));
            }

            if ((!empty($_POST['action']) && $_POST['action'] !== '-1') || (!empty($_POST['action2']) && $_POST['action2'] !== '-1')) {
                $action = sanitize_text_field($_POST['action'] === '-1' ? $_POST['action2'] : $_POST['action']);

                $payments = [];
                if (!empty($_POST['payments']) && is_array($_POST['payments'])) {
                    foreach ($_POST['payments'] as $value) {
                        $data = $this->get_payment_data(intval($value));
                        if (!empty($data)) {
                            $payments[] = $data;
                        }
                    }
                }

                switch ($action) {
                    case 'delete':
                        $this->delete_payment($payments);
                        return;
                }

                wp_redirect($this->get_self_url());
                exit;
            }

            if (!empty($_GET['screen'])) {
                $screen = sanitize_text_field($_GET['screen']);

                switch ($screen) {
                    case 'delete':
                        $this->delete_payment();
                }
            }
           
            $this->objView = new Admin_Payments_Table_View($this);
            $this->objView->set_help();
           
        }

        public function delete_payment($datas = null) {
            $entities = [];
            if (empty($datas)) {
                $data = $this->get_payment_data_from_url();
                $entities = [$data];
            } else {
                $entities = $datas;
            }

            foreach ($entities as $entity) {
                $entity->delete();
            }

            $url_arg = 'payment-deleted';
            wp_safe_redirect(add_query_arg($url_arg, 'true', $this->get_self_url()));
            exit;
        }

        public function view() {
            if (empty($this->objView)) {
                $this->objView = new Admin_Payments_Table_View($this);
            }

            $this->objView->view();
        }

        public function get_cap() {
            return $this->cap;
        }

        public function get_menu_slug() {
            return $this->menu_slug;
        }

        public function get_self_url($args = []) {
            return add_query_arg($args, menu_page_url($this->menu_slug, false));
        }

        public function get_delete_url($id) {
            return $this->get_self_url(['screen' => 'delete', 'id' => $id]);
        }

        public function get_payment_data($id) {
            $entity = new Payments_Entity;
            return $entity->get($id);
        }

        public function get_payment_data_from_url() {
            if (empty($_GET['id'])) {
                wp_safe_redirect($this->get_self_url());
                exit;
            }

            $payment = $this->get_payment_data(intval($_GET['id']));
            return $payment;
        }

        public function get_items($page_num, $page_count, $orderby, $order, $search) {
            $entity = new Payments_Entity();
            return $entity->get_data($page_num, $page_count, $this->get_active_list_filter(), $orderby, $order, $search);
        }

        public function get_items_count($search, $list = '') {
            if($list === '') {
                $list = $this->get_active_list_filter();
            }
            
            $entity = new Payments_Entity();
            return $entity->get_count($list, $search);
        }

        public function get_active_list_filter() {
            if (empty($_GET['list'])) {
                return '0';
            }

            $list = sanitize_text_field($_GET['list']);

            switch ($list) {
                case Payments::STATUS_PENDING:
                case Payments::STATUS_COMPLETED:
                case Payments::STATUS_REFUNDED:
                case Payments::STATUS_ABANDONED:
                    break;
                default:
                    $list = '0';
                    break;
            }

            return $list;
        }

        public function get_list_filter_data() {
            $entity = new Payments_Entity();
            $counts = $entity->get_count_by_status();
            
            $filter_data = array(
                '0' => array(
                    'display' => __('All', 'wpfront-paddle-gateway'),
                    'count' => $this->get_items_count('', 0)
                ),
                Payments::STATUS_PENDING => array(
                    'display' => __('Pending', 'wpfront-paddle-gateway'),
                    'count' => isset($counts[Payments::STATUS_PENDING]) ? $counts[Payments::STATUS_PENDING] : 0
                ),
                Payments::STATUS_COMPLETED => array(
                    'display' => __('Completed', 'wpfront-paddle-gateway'),
                    'count' => isset($counts[Payments::STATUS_COMPLETED]) ? $counts[Payments::STATUS_COMPLETED] : 0
                ),
                Payments::STATUS_REFUNDED => array(
                    'display' => __('Refunded', 'wpfront-paddle-gateway'),
                    'count' => isset($counts[Payments::STATUS_REFUNDED]) ? $counts[Payments::STATUS_REFUNDED] : 0
                ),
                Payments::STATUS_ABANDONED => array(
                    'display' => __('Abandoned', 'wpfront-paddle-gateway'),
                    'count' => isset($counts[Payments::STATUS_ABANDONED]) ? $counts[Payments::STATUS_ABANDONED] : 0
                )
            );
            
            return $filter_data;
        }
       
    }

}