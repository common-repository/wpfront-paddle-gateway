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

require __DIR__ . '/template-admin-paylinks-list.php';
require __DIR__ . '/template-admin-paylinks-add-edit.php';
require __DIR__ . '/template-admin-paylinks-delete.php';

use WPFront\Paddle\Entities\Paylink_Entity;
use WPFront\Paddle\Paylink;

if (!class_exists('\WPFront\Paddle\Admin\Admin_Paylinks')) {

    class Admin_Paylinks {

        protected $cap = 'manage_options';
        protected $main;
        protected $menu_slug;
        protected $error;
        protected $objView = null;

        public function __construct($main) {
            $this->main = $main;

            $this->main->add_admin_menu(
                    __('Paddle Paylinks', 'wpfront-paddle-gateway'),
                    __('Paylinks', 'wpfront-paddle-gateway'),
                    $this->get_cap(),
                    'paylinks',
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

                $paylinks = [];
                if (!empty($_POST['paylinks']) && is_array($_POST['paylinks'])) {
                    foreach ($_POST['paylinks'] as $value) {
                        $data = $this->get_paylinks_data(intval($value));
                        if (!empty($data)) {
                            $paylinks[] = $data;
                        }
                    }
                }

                switch ($action) {
                    case 'delete':
                        $this->delete_paylinks($paylinks);
                        return;
                }

                wp_redirect($this->get_self_url());
                exit;
            }

            if (!empty($_GET['screen'])) {
                $screen = sanitize_text_field($_GET['screen']);

                switch ($screen) {

                    case 'add-new':
                    case 'edit':
                        $this->add_edit_paylinks($screen);
                        return;

                    case 'delete':
                        $this->delete_paylinks();
                        return;

                    default:
                        break;
                }
            }

            $this->objView = new Admin_Paylinks_Table_View($this);
            $this->objView->set_help();
        }

        public function get_error() {
            if (empty($this->error)) {
                return null;
            }

            return $this->error;
        }

        public function add_edit_paylinks($screen) {
            $data = null;
            if ($screen == 'edit') {
                $data = $this->get_paylink_data_from_url();
                if (empty($data)) {
                    return;
                }
            }

            $this->objView = new Admin_Paylinks_Add_Edit_View(
                    $this,
                    $data
            );
            $this->objView->set_help();

            if (!empty($_POST['submit'])) {
                check_admin_referer('wp-paddle-paylinks');
                if (!empty($data)) {
                    $entity = $data;
                } else {
                    $entity = new Paylink_Entity();
                }

                $name = sanitize_text_field($_POST['name']);
                if (empty($name)) {
                    $this->error = __('Name is required.', 'wpfront-paddle-gateway');
                    return;
                }
                
                $label = sanitize_text_field($_POST['label']);
                if (empty($label)) {
                    $this->error = __('Link Label is required.', 'wpfront-paddle-gateway');
                    return;
                }
                               
                $title = sanitize_text_field($_POST['title']);
                if (empty($title)) {
                    $this->error = __('Product Title is required.', 'wpfront-paddle-gateway');
                    return;
                }            
                
                $price = floatval($_POST['price']);

                if (!empty($price)) {
                    $price = round($price, 2);
                }

                $allow_atts = (!empty($_POST['allow_atts']));

                $entity->name = $name;
                $entity->label = $label;
                $entity->title = $title;
                $entity->price = $price;
                $entity->allow_atts = $allow_atts;


                if ($screen == 'add-new') {
                    $result = $entity->add();
                    $url_arg = 'paylink-added';
                } else if ($screen == 'edit') {
                    $result = $entity->update();
                    $url_arg = 'paylink-updated';
                }

                wp_safe_redirect(add_query_arg($url_arg, 'true', $this->get_edit_url($entity->id)));
                exit;
            }
        }

        public function delete_paylinks($datas = null) {
            $entities = [];
            if (empty($datas)) {
                $data = $this->get_paylink_data_from_url();
                $entities = [$data];
            } else {
                $entities = $datas;
            }

            if (empty($entities)) {
                wp_redirect($this->get_self_url());
                exit;
            }

            if (!empty($_POST['submit'])) {
                check_admin_referer('bulk-action-delete-paylink');
                foreach ($entities as $entity) {
                    $entity->delete();
                }
                $url_arg = 'paylink-deleted';
                wp_safe_redirect(add_query_arg($url_arg, 'true', $this->get_self_url()));
                exit;
            }

            $this->objView = new Admin_Paylinks_Delete_View($this, $entities);
            $this->objView->set_help();
        }

        public function view() {
            if (empty($this->objView)) {
                $this->objView = new Admin_Paylinks_Table_View($this);
            }

            $this->objView->view();
        }

        public function get_cap() {
            return $this->cap;
        }

        public function get_add_new_url() {
            $add_new = ['screen' => 'add-new'];
            return $this->get_self_url($add_new);
        }

        public function get_edit_url($id) {
            return $this->get_self_url(['screen' => 'edit', 'id' => $id]);
        }

        public function get_delete_url($id) {
            if (empty($id)) {
                return $this->get_self_url(['screen' => 'delete']);
            }

            return $this->get_self_url(['screen' => 'delete', 'id' => $id]);
        }

        public function search($search) {
            $entity = new Paylink_Entity();
            $paylinks = $entity->get_all();

            if (empty($search)) {
                return $paylinks;
            }

            foreach ($paylinks as $name => $item) {
                if (strpos($item->name, $search) !== false) {
                    continue;
                }

                if (strpos($item->label, $search) !== false) {
                    continue;
                }

                unset($paylinks[$name]);
            }

            return $paylinks;
        }

        public function get_paylink_data_from_url() {
            if (empty($_GET['id'])) {
                wp_safe_redirect($this->get_self_url());
                exit;
            }

            $paylinks = $this->get_paylinks_data(intval($_GET['id']));
            return $paylinks;
        }

        public function get_paylinks_data($id) {
            $entity = new Paylink_Entity;
            $lists = $entity->get_all();
            if (!empty($lists[$id])) {
                return $lists[$id];
            }

            return null;
        }

        public function get_self_url($args = []) {
            return add_query_arg($args, menu_page_url($this->menu_slug, false));
        }

        public function get_menu_slug() {
            return $this->menu_slug;
        }

        public function get_shortcode($entity) {
            return Paylink::instance()->get_shortcode($entity, true);
        }
 
    }

}