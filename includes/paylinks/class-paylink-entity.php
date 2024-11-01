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

namespace WPFront\Paddle\Entities;

if (!defined('ABSPATH')) {
    exit();
}

use WPFront\Paddle\Settings;
use WPFront\Paddle\WPFront_Paddle_Gateway;

if (!class_exists('\WPFront\Paddle\Entities\Paylink_Entity')) {

    class Paylink_Entity {

        /**
         * Primary key.
         *
         * @var int
         */
        public $id;

        /**
         * Paylink Name.
         *
         * @var string
         */
        public $name;

        /**
         * Paylink label.
         *
         * @var string
         */
        public $label;

        /**
         * Paylink price.
         *
         * @var float
         */
        public $price;

        /**
         * Product title.
         *
         * @var string
         */
        public $title;

        /**
         * Product title.
         *
         * @var int
         */
        public $allow_atts;

        public function __construct() {
            $this->create_table();
        }

        private static function table_name() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpfront_paddle_paylink';

            return $table_name;
        }

        private function create_table() {
            if($this->cache_get('table_created') == 1) {
                return;
            }
            
            if (defined('WP_UNINSTALL_PLUGIN')) {
                return;
            }
            
            $this->cache_set('table_created', 1);

            $table_name = self::table_name();
            $key = $table_name . '-db-version';
            $db_version = Settings::instance()->get_setting($key);
            if (!empty($db_version)) {
                if (version_compare($db_version, \WPFront\Paddle\WPFront_Paddle_Gateway::VERSION, '>=')) {
                    return;
                }
            }

            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (\n"
                    . "id int(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                    . "name varchar(191) DEFAULT NULL,\n"
                    . "label varchar(191) DEFAULT NULL,\n"
                    . "price numeric(10, 2) DEFAULT NULL,\n"
                    . "title varchar(191) DEFAULT NULL,\n"
                    . "allow_atts tinyint(1) DEFAULT NULL \n,"
                    . "PRIMARY KEY  (id)\n"
                    . ") $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);

            Settings::instance()->update_setting($key, \WPFront\Paddle\WPFront_Paddle_Gateway::VERSION);
        }

        public function add() {
            global $wpdb;
            $table_name = self::table_name();

            $result = $wpdb->insert(
                    $table_name,
                    array(
                        'name' => $this->name,
                        'label' => $this->label,
                        'price' => $this->price,
                        'title' => $this->title,
                        'allow_atts' => $this->allow_atts
                    ),
                    array(
                        '%s',
                        '%s',
                        '%f',
                        '%s',
                        '%d'
                    )
            );

            $this->cache_delete('all_paylinks');

            if ($result === false) {
                return false;
            } else {
                $this->id = $wpdb->insert_id;
                return true;
            }
        }

        public function update() {
            global $wpdb;
            $tablename = self::table_name();

            $result = $wpdb->update(
                    $tablename,
                    array(
                        'name' => $this->name,
                        'label' => $this->label,
                        'price' => $this->price,
                        'title' => $this->title,
                        'allow_atts' => $this->allow_atts
                    ),
                    array(
                        'id' => $this->id
                    ),
                    array(
                        '%s',
                        '%s',
                        '%f',
                        '%s',
                        '%d'
                    ),
                    array(
                        '%d'
                    )
            );

            $this->cache_delete('all_paylinks');

            return $result !== false;
        }

        public function delete() {
            global $wpdb;
            $tablename = self::table_name();

            $sql = "DELETE FROM $tablename WHERE id = %d";
            $sql = $wpdb->prepare($sql, $this->id);
            $result = $wpdb->query($sql);

            $this->cache_delete('all_paylinks');

            return !empty($result);
        }

        public function get_all() {
            $found = false;
            $value = $this->cache_get('all_paylinks', $found);
            if ($found) {
                return $value;
            }
            $table_name = self::table_name();

            $sql = "SELECT id, name, label, price, title, allow_atts "
                    . "FROM $table_name ";

            global $wpdb;
            $results = $wpdb->get_results($sql);

            $data = array();
            foreach ($results as $value) {
                $entity = new Paylink_Entity();

                $entity->id = intval($value->id);
                $entity->name = $value->name;
                $entity->label = $value->label;
                $entity->price = floatval($value->price);
                $entity->title = $value->title;
                $entity->allow_atts = $value->allow_atts;

                $data[$entity->id] = $entity;
            }

            $this->cache_set('all_paylinks', $data);

            return $data;
        }

        public function get($id) {
            $data = $this->get_all();
            if (empty($data[$id])) {
                return null;
            }

            return $data[$id];
        }

        protected function cache_set($key, $value) {
            wp_cache_set($key, $value, self::table_name() . WPFront_Paddle_Gateway::VERSION);
        }

        protected function cache_get($key, &$found = false) {
            $value = wp_cache_get($key, self::table_name() . WPFront_Paddle_Gateway::VERSION, false, $found);
            if ($found) {
                return $value;
            }

            return null;
        }

        protected function cache_delete($key) {
            wp_cache_delete($key, self::table_name() . WPFront_Paddle_Gateway::VERSION);
        }

        public static function uninstall() {
            $table_name = self::table_name();

            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }

    }

}