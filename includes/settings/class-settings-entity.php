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

use WPFront\Paddle\WPFront_Paddle_Gateway;

if (!class_exists('\WPFront\Paddle\Entities\Settings_Entity')) {

    class Settings_Entity {

        /**
         * Primary key.
         *
         * @var int
         */
        public $id;

        /**
         * Settings Name.
         *
         * @var string
         */
        public $settings_name;

        /**
         * Settings Value.
         *
         * @var mixed
         */
        public $settings_value;

        public function __construct() {
            $this->create_table();
        }

        private static function table_name() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpfront_paddle_settings';

            return $table_name;
        }

        private function create_table() {
            if ($this->cache_get('table_created') == 1) {
                return;
            }

            if (defined('WP_UNINSTALL_PLUGIN')) {
                return;
            }

            $this->cache_set('table_created', 1);

            $table_name = self::table_name();

            $key = $table_name . '-db-version';
            $db_version = get_option($key);
            if(!empty($db_version)) {
                if(version_compare($db_version, \WPFront\Paddle\WPFront_Paddle_Gateway::VERSION, '>=')) {
                    return;
                }
            }

            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (\n"
                    . "id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                    . "settings_name varchar(191) DEFAULT NULL,\n"
                    . "settings_value longtext DEFAULT NULL,\n"
                    . "PRIMARY KEY  (id),\n"
                    . "UNIQUE KEY settings_name (settings_name)\n"
                    . ") $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);
            
            //set edd integration to true by default
            
            $setting_name = 'integrate-edd';
            $sql = $wpdb->prepare("SELECT EXISTS(SELECT 1 FROM $table_name WHERE settings_name = %s)", $setting_name);
            $result = $wpdb->get_var($sql);
            if(empty($result)) {
                $sql = $wpdb->prepare("INSERT INTO $table_name(settings_name, settings_value) VALUES(%s, %s)", $setting_name, 1);
                $wpdb->query($sql);
            }

            update_option($key, \WPFront\Paddle\WPFront_Paddle_Gateway::VERSION);
        }

        public function update() {
            global $wpdb;
            $table_name = self::table_name();
            $sql = $wpdb->prepare("SELECT EXISTS(SELECT 1 FROM $table_name WHERE settings_name = %s)", $this->settings_name);
            $result = $wpdb->get_var($sql);

            if (empty($result)) {
                $result = $wpdb->insert(
                        $table_name,
                        array(
                            'settings_name' => $this->settings_name,
                            'settings_value' => maybe_serialize($this->settings_value)
                        ),
                        array(
                            '%s',
                            '%s'
                        )
                );
            } else {
                $result = $wpdb->update(
                        $table_name,
                        array(
                            'settings_value' => maybe_serialize($this->settings_value)
                        ),
                        array(
                            'settings_name' => $this->settings_name
                        ),
                        array(
                            '%s'
                        ),
                        array(
                            '%s'
                        )
                );
            }

            if($result !== false) {
                $this->cache_set($this->settings_name, $this->settings_value);
                
                return true;
            }
            
            return false;
        }

        
        public function get($settings_name) {
            $found = false;
            $value = $this->cache_get($settings_name, $found);
            if ($found) {
                return $value;
            }

            global $wpdb;
            $table_name = self::table_name();
            $sql = $wpdb->prepare("SELECT settings_value FROM $table_name WHERE settings_name = %s", $settings_name);
            $result = $wpdb->get_var($sql);

            if ($result !== null) {
                $result = maybe_unserialize($result);
            }
            
            $this->cache_set($settings_name, $result);

            return $result;
        }
        
        public function delete($settings_name) {
            $this->cache_delete($settings_name);

            global $wpdb;
            $table_name = self::table_name();
            $sql = $wpdb->prepare("DELETE FROM $table_name WHERE settings_name = %s", $settings_name);
            $result = $wpdb->query($sql);

            return $result;
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

            $db_key = $table_name . '-db-version';
            delete_option($db_key);

            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }

    }

}


