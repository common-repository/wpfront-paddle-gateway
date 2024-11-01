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

require __DIR__ . '/template-admin-dashboard.php';

use WPFront\Paddle\Payments;
use WPFront\Paddle\Entities\Payments_Entity;
use WPFront\Paddle\WPFront_Paddle_Gateway;

if (!class_exists('\WPFront\Paddle\Admin\Admin_Dashboard')) {

    class Admin_Dashboard {

        protected $cap = 'manage_options';
        protected $main;
        protected $menu_slug;
        protected $error;
        protected $objView = null;

        public function __construct($main) {
            $this->main = $main;

            $this->main->add_admin_menu(
                    __('Paddle Dashboard', 'wpfront-paddle-gateway'),
                    __('Dashboard', 'wpfront-paddle-gateway'),
                    $this->get_cap(),
                    'dashboard',
                    array($this, 'view'),
                    10,
                    array($this, 'menu_callback')
            );
        }

        public function menu_callback($hook_suffix, $menu_slug) {
            $this->menu_slug = $menu_slug;

            add_action("load-$hook_suffix", array($this, 'load_view'));
            add_action("admin_print_scripts-$hook_suffix", array($this, 'admin_print_scripts'));
        }

        public function admin_print_scripts() {
            $js = 'js/chart.min.js';
            wp_enqueue_script('chart.js', WPFront_Paddle_Gateway::instance()->get_asset_url($js), array('jquery'), '3.5.0', true);
        }

        public function load_view() {
            
        }

        public function view() {
            $obj = new Admin_Dashboard_View($this);
            $obj->view();
        }

        public function get_cap() {
            return $this->cap;
        }

        public function get_menu_slug() {
            return $this->menu_slug;
        }

        public function get_graph_data() {
            $period = 'last_30_days';
            if (!empty($_GET['period'])) {
                $period = sanitize_text_field($_GET['period']);
            }

            $entity = new Payments_Entity();
            $key = "get_graph_data-$period";
            $data = $entity->cache_get($key);
            if(!empty($data)) {
                return $data;
            }
            
            $group_by = 'DATE(date)';
            $i = new \DateInterval('P1D');
            $format = 'Y-m-d';
            
            $interval = $this->get_date_intervals($period);
            $from = $interval[0];
            $to = $interval[1];
            switch ($period) {
                case 'this_year':
                case 'previous_year':
                    $group_by = 'MONTHNAME(date)';
                    $i = new \DateInterval('P1M');
                    $format = 'F';
                    break;
            }
            
            $values = $entity->get_earnings_over_time($from, $to, Payments::STATUS_COMPLETED, $group_by);

            $begin = date_create($from);
            $end = date_create($to);
            $end->add(new \DateInterval('PT1S'));
            $period = new \DatePeriod($begin, $i, $end);

            $data = array();
            $i = 0;
            foreach ($period as $d) {
                $day = $d->format($format);
                if (!empty($values[$day])) {
                    $values[$day]->x = $i;
                    $values[$day]->D = __($values[$day]->D);
                    if($values[$day]->C === 'USD') {
                        $values[$day]->C = '$';
                    }
                    $data[] = $values[$day];
                } else {
                    $data[] = (object) array('x' => $i, 'D' => __($day), 'E' => 0, 'S' => 0, 'C' => '');
                }
                $i++;
            }

            $entity->cache_set($key, $data);
            
            return $data;
        }

        public function get_total_earnings_sales() {
            $entity = new Payments_Entity();
            $key = 'get_total_earnings_sales';
            $data = $entity->cache_get($key);
            if(!empty($data)) {
                return $data;
            }
            
            $data = array();
            $periods = ['today' => __('Today', 'wpfront-paddle-gateway'), 'last_30_days' => __('Last 30 Days', 'wpfront-paddle-gateway'), 'previous_month' => __('Previous Month', 'wpfront-paddle-gateway'), 'this_year' => __('This Year', 'wpfront-paddle-gateway')]; 
            
            foreach ($periods as $period => $label) {
                $earnings = 0;
                $sales = 0;
                
                $interval = $this->get_date_intervals($period);
                $from = $interval[0];
                $to = $interval[1];
                
                $values = $entity->get_earnings_over_time($from, $to, Payments::STATUS_COMPLETED, '1');
                $values = reset($values);
                
                if(empty($values)) {
                    $values = new \stdClass();
                    $values->S = 0;
                    $values->E = 0;
                    $values->C = '';
                }
                
                $values->L = $label;
                
                if($values->C === 'USD') {
                    $values->C = '$';
                }
                
                $data[$period] = $values;
            }
            
            $entity->cache_set($key, $data);
            
            return $data;
        }
        
        protected function get_date_intervals($interval) {
            switch ($interval) {
                case 'today':
                    $from = current_time('Y-m-d');
                    $to = $from;
                    break;
                
                case 'this_month':
                    $from = current_time('Y-m-01');
                    $to = current_time('Y-m-t');
                    break;

                case 'previous_month':
                    $current_date = current_time('Y-m-01');
                    $to = date('Y-m-d', strtotime($current_date . ' -1 day'));
                    $from = date('Y-m-d', strtotime($current_date . ' -1 month'));
                    break;

                case 'this_year':
                    $from = current_time('Y-01-01');
                    $to = current_time('Y-12-31');
                    break;

                case 'previous_year':
                    $Y = current_time('Y') - 1;
                    $from = "$Y-01-01";
                    $to = "$Y-12-31";
                    break;

                default: //last_30_days
                    $current_date = current_time('Y-m-d');
                    $to = date('Y-m-d', strtotime($current_date . ' -1 day'));
                    $from = date('Y-m-d', strtotime($current_date . ' -30 days'));
                    break;
            }
            
            return [$from, $to];
        }
      
    }

}