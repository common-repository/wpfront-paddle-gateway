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

if (!class_exists('\WPFront\Paddle\Entities\Payments_Entity')) {

    class Payments_Entity {

        /**
         * Primary key.
         *
         * @var int
         */
        public $id;

        /**
         * Payment date.
         *
         * @var string
         */
        public $date;

        /**
         * Payment User.
         *
         * @var int
         */
        public $user_id;

        /**
         * Payment Paylink id.
         *
         * @var int
         */
        public $paylink_id;

        /**
         * Payment Status.
         *
         * @var int
         */
        public $status;

        /**
         * Payment Checkout id.
         *
         * @var string
         */
        public $checkout_id;

        /**
         * Payment Order id.
         *
         * @var string
         */
        public $order_id;

        /**
         * Payment Passthrough.
         *
         * @var string
         */
        public $passthrough;

        /**
         * Payment Customer Name.
         *
         * @var string
         */
        public $customer_name;

        /**
         * Payment Email.
         *
         * @var string
         */
        public $email;

        /**
         * Payment Country.
         *
         * @var string
         */
        public $country;

        /**
         * Payment Product id.
         *
         * @var int
         */
        public $product_id;

        /**
         * Payment Product Name.
         *
         * @var string
         */
        public $product_name;

        /**
         * Payment Quantity.
         *
         * @var int
         */
        public $quantity;

        /**
         * Payment Coupon.
         *
         * @var string
         */
        public $coupon;

        /**
         * Payment method.
         *
         * @var string
         */
        public $payment_method;

        /**
         * Payment Currency.
         *
         * @var string
         */
        public $currency;

        /**
         * Payment Sale Gross.
         *
         * @var float
         */
        public $sale_gross;

        /**
         * Payment Receipt URL.
         *
         * @var string
         */
        public $receipt_url;

        /**
         * Payment Balance Currency.
         *
         * @var float
         */
        public $balance_currency;

        /**
         * Payment Balance Gross.
         *
         * @var float
         */
        public $balance_gross;

        /**
         * Payment Balance Fee.
         *
         * @var float
         */
        public $balance_fee;

        /**
         * Payment Balance Tax.
         *
         * @var float
         */
        public $balance_tax;

        /**
         * Payment Balance Earnings.
         *
         * @var float
         */
        public $balance_earnings;

        /**
         * Payment Asking Price.
         *
         * @var float
         */
        public $asking_price;

        /**
         * Payment Payload.
         *
         * @var array
         */
        public $payload;

        public function __construct() {
            $this->create_table();
        }

        private static function table_name() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpfront_paddle_payments';

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
            $db_version = Settings::instance()->get_setting($key);
            if (!empty($db_version)) {
                if (version_compare($db_version, \WPFront\Paddle\WPFront_Paddle_Gateway::VERSION, '>=')) {
                    return;
                }
            }
            
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (\n"
                    . "id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                    . "date DATETIME DEFAULT NULL,\n"
                    . "user_id bigint(20) UNSIGNED DEFAULT NULL,\n"
                    . "paylink_id int(20) UNSIGNED DEFAULT NULL,\n"
                    . "status tinyint(1) DEFAULT NULL,\n"
                    . "checkout_id varchar(1000) DEFAULT NULL,\n"
                    . "order_id varchar(1000) DEFAULT NULL,\n"
                    . "passthrough varchar(1000) DEFAULT NULL,\n"
                    . "customer_name varchar(1000) DEFAULT NULL,\n"
                    . "email varchar(1000) DEFAULT NULL,\n"
                    . "country varchar(250) DEFAULT NULL,\n"
                    . "product_id bigint(20) UNSIGNED DEFAULT NULL,\n"
                    . "product_name varchar(1000) DEFAULT NULL,\n"
                    . "quantity int(10) DEFAULT NULL,\n"
                    . "coupon varchar(1000) DEFAULT NULL,\n"
                    . "payment_method varchar(250) DEFAULT NULL,\n"
                    . "currency varchar(100) DEFAULT NULL,\n"
                    . "asking_price numeric(10, 2) DEFAULT NULL,\n"
                    . "sale_gross numeric(10, 2) DEFAULT NULL,\n"
                    . "receipt_url varchar(4000) DEFAULT NULL,\n"
                    . "balance_currency varchar(250) DEFAULT NULL,\n"
                    . "balance_gross numeric(10, 2) DEFAULT NULL,\n"
                    . "balance_fee numeric(10, 2) DEFAULT NULL,\n"
                    . "balance_tax numeric(10, 2) DEFAULT NULL,\n"
                    . "balance_earnings numeric(10, 2) DEFAULT NULL,\n"
                    . "payload longtext DEFAULT NULL,\n"
                    . "PRIMARY KEY  (id),\n"
                    . "KEY product_name (product_name(191), status),\n"
                    . "KEY date (date, status),\n"
                    . "KEY status (status),\n"
                    . "KEY email (email(191), status),\n"
                    . "KEY customer_name (customer_name(191), status),\n"
                    . "KEY user_id (user_id, status),\n"
                    . "KEY paylink_id (paylink_id, status),\n"
                    . "KEY checkout_id (checkout_id(191)),\n"
                    . "KEY order_id (order_id(191)),\n"
                    . "KEY passthrough (passthrough(191), status),\n"
                    . "KEY country (country(191), status),\n"
                    . "KEY product_id (product_id, status)\n"
                    . ") $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);

            Settings::instance()->update_setting($key, \WPFront\Paddle\WPFront_Paddle_Gateway::VERSION);
        }

        private function get_field_formats() {
            return array(
                'date' => '%s',
                'user_id' => '%d',
                'paylink_id' => '%d',
                'status' => '%d',
                'checkout_id' => '%s',
                'order_id' => '%s',
                'passthrough' => '%s',
                'customer_name' => '%s',
                'email' => '%s',
                'country' => '%s',
                'product_id' => '%d',
                'product_name' => '%s',
                'quantity' => '%d',
                'coupon' => '%s',
                'payment_method' => '%s',
                'currency' => '%s',
                'asking_price' => '%f',
                'sale_gross' => '%f',
                'receipt_url' => '%s',
                'balance_currency' => '%s',
                'balance_gross' => '%f',
                'balance_fee' => '%f',
                'balance_tax' => '%f',
                'balance_earnings' => '%f',
                'payload' => '%s'
            );
        }

        public function add() {
            global $wpdb;
            $table_name = self::table_name();

            $fileds = $this->get_field_formats();

            $values = array();
            $formats = array();
            foreach ($fileds as $key => $f) {
                $values[$key] = $this->$key;
                $formats[] = $f;
            }

            $values['payload'] = maybe_serialize($values['payload']);

            $result = $wpdb->insert($table_name, $values, $formats);

            $this->cache_flush();

            if ($result === false) {
                return false;
            } else {
                $this->id = $wpdb->insert_id;
            }

            return true;
        }

        public function update() {
            if (empty($this->id)) {
                return $this->add();
            }

            $fileds = $this->get_field_formats();

            $values = array();
            $formats = array();
            foreach ($fileds as $key => $f) {
                $values[$key] = $this->$key;
                $formats[] = $f;
            }

            $values['payload'] = maybe_serialize($values['payload']);

            global $wpdb;
            $tablename = self::table_name();
            $result = $wpdb->update(
                    $tablename,
                    $values,
                    array(
                        'id' => $this->id
                    ),
                    $formats,
                    array(
                        '%d'
                    )
            );

            $this->cache_flush();

            return $result !== false;
        }

        public function delete() {
            global $wpdb;
            $tablename = self::table_name();

            $sql = "DELETE FROM $tablename WHERE id = %d";
            $sql = $wpdb->prepare($sql, $this->id);
            $result = $wpdb->query($sql);

            $this->cache_flush();

            return !empty($result);
        }

        public function get_data($page_num, $page_count, $status = 0, $orderby = 'id', $order = 'desc', $search = '') {
            $cache_key = "get_data-$page_num-$page_count-$status-$orderby-$order-$search";
            $data = $this->cache_get($cache_key);
            if ($data !== null) {
                return $data;
            }

            $table_name = self::table_name();

            $sql = "SELECT id, date, user_id, paylink_id, status, checkout_id, order_id, passthrough, customer_name, email, country, product_id, product_name, quantity, coupon, payment_method, currency, asking_price, sale_gross, receipt_url, balance_currency, balance_gross, balance_fee, balance_tax, balance_earnings, payload "
                    . "FROM $table_name ";

            global $wpdb;

            $where = '';

            if (!empty($status)) {
                $where .= $wpdb->prepare('status = %d ', $status);
            }

            if (!empty($search)) {
                $search = $wpdb->esc_like($search);
                $search = "{$search}%";

                if (!empty($where)) {
                    $where .= 'AND ';
                }

                $where .= $wpdb->prepare('product_name LIKE %s OR email LIKE %s OR customer_name LIKE %s ', $search, $search, $search);
            }

            if (!empty($where)) {
                $sql .= "WHERE $where ";
            }

            if (!empty($orderby)) {
                $sql .= "ORDER BY $orderby ";

                if (!empty($order)) {
                    $sql .= "$order ";
                }
            }

            $page_num = ($page_num - 1) * $page_count;
            $sql .= "LIMIT $page_num, $page_count";

            $results = $wpdb->get_results($sql);

            $data = [];
            foreach ($results as $value) {
                $entity = $this->get_payment_data($value);
                $data[$entity->id] = $entity;
            }

            $this->cache_set($cache_key, $data);

            return $data;
        }

        public function get_count($status = 0, $search = '') {
            $cache_key = "get_count-$status-$search";
            $data = $this->cache_get($cache_key);
            if ($data !== null) {
                return $data;
            }

            $table_name = self::table_name();

            $sql = "SELECT COUNT(1) FROM $table_name ";

            global $wpdb;

            $where = '';

            if (!empty($status)) {
                $where .= $wpdb->prepare('status = %d ', $status);
            }

            if (!empty($search)) {
                $search = $wpdb->esc_like($search);
                $search = "{$search}%";

                if (!empty($where)) {
                    $where .= 'AND ';
                }

                $where .= $wpdb->prepare('product_name LIKE %s OR email LIKE %s OR customer_name LIKE %s ', $search, $search, $search);
            }

            if (!empty($where)) {
                $sql .= "WHERE $where ";
            }

            $result = intval($wpdb->get_var($sql));

            $this->cache_set($cache_key, $result);

            return $result;
        }

        public function get_count_by_status() {
            $cache_key = "get_count_by_status";
            $data = $this->cache_get($cache_key);
            if ($data !== null) {
                return $data;
            }

            $table_name = self::table_name();

            $sql = "SELECT status, COUNT(1) AS c FROM $table_name "
                    . "GROUP BY status";

            global $wpdb;

            $results = $wpdb->get_results($sql);
            $count = array();

            foreach ($results as $value) {
                $count[$value->status] = $value->c;
            }

            $this->cache_set($cache_key, $count);

            return $count;
        }

        public function get($id) {
            $cache_key = "get-$id";
            $data = $this->cache_get($cache_key);
            if ($data !== null) {
                return $data;
            }

            global $wpdb;
            $table_name = self::table_name();

            $sql = "SELECT id, date, user_id, paylink_id, status, checkout_id, order_id, passthrough, customer_name, email, country, product_id, product_name, quantity, coupon, payment_method, currency, asking_price, sale_gross, receipt_url, balance_currency, balance_gross, balance_fee, balance_tax, balance_earnings, payload "
                    . "FROM $table_name WHERE id=%d";

            $sql = $wpdb->prepare($sql, $id);

            $value = $wpdb->get_row($sql);
            if (!empty($value)) {
                $entity = $this->get_payment_data($value);
                $entity->set_cache();

                return $entity;
            }

            return null;
        }

        public function get_payment_by_order_id($order_id) {
            $cache_key = "orderid-$order_id";
            $data = $this->cache_get($cache_key);
            if ($data !== null) {
                return $data;
            }

            global $wpdb;
            $table_name = self::table_name();

            $sql = "SELECT id, date, user_id, paylink_id, status, checkout_id, order_id, passthrough, customer_name, email, country, product_id, product_name, quantity, coupon, payment_method, currency, asking_price, sale_gross, receipt_url, balance_currency, balance_gross, balance_fee, balance_tax, balance_earnings, payload "
                    . "FROM $table_name WHERE order_id=%s";

            $sql = $wpdb->prepare($sql, $order_id);

            $value = $wpdb->get_row($sql);
            if (!empty($value)) {
                $entity = $this->get_payment_data($value);
                $entity->set_cache();

                return $entity;
            }

            return null;
        }

        public function get_payments_by_passthrough($passthrough, $status, $limit = 1) {
            if($limit === 1) {
                $cache_key = "passthrough-$passthrough-$status";
                $data = $this->cache_get($cache_key);
                if ($data !== null) {
                    return $data;
                }
            }

            $table_name = self::table_name();

            $sql = "SELECT id, date, user_id, paylink_id, status, checkout_id, order_id, passthrough, customer_name, email, country, product_id, product_name, quantity, coupon, payment_method, currency, asking_price, sale_gross, receipt_url, balance_currency, balance_gross, balance_fee, balance_tax, balance_earnings, payload "
                    . "FROM $table_name WHERE passthrough=%s and status=%d ORDER BY id DESC LIMIT $limit";

            global $wpdb;
            $sql = $wpdb->prepare($sql, $passthrough, $status);

            $results = $wpdb->get_results($sql);

            $entities = array();
            foreach ($results as $value) {
                $entity = $this->get_payment_data($value);
                $entities[] = $entity;
            }

            if (!empty($entities)) {
                $entities[0]->set_cache();
            }

            return $entities;
        }

        public function get_payments_by_date($from, $to, $status, $limit) {
            $table_name = self::table_name();

            $sql = "SELECT id, date, user_id, paylink_id, status, checkout_id, order_id, passthrough, customer_name, email, country, product_id, product_name, quantity, coupon, payment_method, currency, asking_price, sale_gross, receipt_url, balance_currency, balance_gross, balance_fee, balance_tax, balance_earnings, payload "
                    . "FROM $table_name WHERE date BETWEEN %s AND %s AND status = %d ORDER BY date DESC LIMIT $limit";

            global $wpdb;
            $sql = $wpdb->prepare($sql, $from, $to, $status);

            $results = $wpdb->get_results($sql);
            $entities = array();
            foreach ($results as $value) {
                $entity = $this->get_payment_data($value);
                $entities[] = $entity;
            }

            return $entities;
        }

        public function get_earnings_over_time($from, $to, $status, $group_by) {
            $cache_key = "get_earnings_over_time-$from-$to-$status-$group_by";
            $data = $this->cache_get($cache_key);
            if ($data !== null) {
                return $data;
            }

            $to = new \DateTime($to);
            $to->modify('+1 day');
            $to = $to->format('Y-m-d');

            $table_name = self::table_name();

            $sql = "SELECT $group_by AS D, balance_currency AS C, SUM(balance_earnings) AS E, COUNT(date) AS S "
                    . "FROM $table_name WHERE %s <= date and date < %s AND STATUS=%d GROUP BY D, C";

            global $wpdb;
            $sql = $wpdb->prepare($sql, $from, $to, $status);

            $results = $wpdb->get_results($sql);
            $data = array();
            foreach ($results as $result) {
                $data[$result->D] = $result;
            }

            $this->cache_set($cache_key, $data);

            return $data;
        }

        protected function get_payment_data($value) {
            $entity = new Payments_Entity();

            $entity->id = intval($value->id);
            $entity->date = $value->date;
            $entity->user_id = $value->user_id;
            $entity->paylink_id = $value->paylink_id;
            $entity->status = intval($value->status);
            $entity->checkout_id = $value->checkout_id;
            $entity->order_id = $value->order_id;
            $entity->passthrough = $value->passthrough;
            $entity->customer_name = $value->customer_name;
            $entity->email = $value->email;
            $entity->country = $value->country;
            $entity->product_id = $value->product_id;
            $entity->product_name = $value->product_name;
            $entity->quantity = $value->quantity;
            $entity->coupon = $value->coupon;
            $entity->payment_method = $value->payment_method;
            $entity->currency = $value->currency;
            $entity->asking_price = floatval($value->asking_price);
            $entity->sale_gross = floatval($value->sale_gross);
            $entity->receipt_url = $value->receipt_url;
            $entity->balance_currency = $value->balance_currency;
            $entity->balance_gross = $value->balance_gross;
            $entity->balance_fee = $value->balance_fee;
            $entity->balance_tax = $value->balance_tax;
            $entity->balance_earnings = floatval($value->balance_earnings);

            if (empty($value->payload)) {
                $entity->payload = [];
            } else {
                $entity->payload = maybe_unserialize($value->payload);
            }

            return $entity;
        }

        protected function set_cache() {
            $cache_key = "passthrough-{$this->passthrough}-{$this->status}";
            $this->cache_set($cache_key, $this);

            $cache_key = "orderid-{$this->order_id}";
            $this->cache_set($cache_key, $this);

            $cache_key = "get-{$this->id}";
            $this->cache_set($cache_key, $this);
        }

        protected function get_cache_key($key) {
            $ns_key = self::table_name() . '-group-ns';
            $ns = wp_cache_get($ns_key);
            if (empty($ns)) {
                wp_cache_set($ns_key, 1);
                $ns = 1;
            }

            return $key . '-' . $ns;
        }

        public function cache_set($key, $value) {
            wp_cache_set($this->get_cache_key($key), $value, self::table_name() . WPFront_Paddle_Gateway::VERSION);
        }

        public function cache_get($key, &$found = false) {
            $value = wp_cache_get($this->get_cache_key($key), self::table_name() . WPFront_Paddle_Gateway::VERSION, false, $found);
            if ($found) {
                return $value;
            }

            return null;
        }

        protected function cache_flush() {
            $ns_key = self::table_name() . '-group-ns';
            wp_cache_incr($ns_key);
        }

        public static function uninstall() {
            $table_name = self::table_name();

            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }

    }

}