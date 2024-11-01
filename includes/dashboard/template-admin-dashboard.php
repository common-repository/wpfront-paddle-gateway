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

if (!class_exists('\WPFront\Paddle\Admin\Admin_Dashboard_View')) {

    class Admin_Dashboard_View {

        /**
         *
         * @var Admin_Dashboard
         */
        protected $controller;

        public function __construct($controller) {
            $this->controller = $controller;
        }

        public function view() {
            ?>
            <div class="wrap paddle-admin-settings">
                <?php $this->title(); ?>
                <?php $this->render(); ?>
            </div>
            <?php
        }

        protected function title() {
            ?>
            <h2>
                <?php echo __('Dashboard', 'wpfront-paddle-gateway'); ?>
            </h2>
            <?php
        }

        public function render() {
            ?>
            <style type="text/css">
                div.stats div.tile {
                    float: left;
                    background-color: white;
                    margin-right: 10px;
                    margin-bottom: 10px;
                    padding: 10px;
                    font-size: 1.2em;
                    border: 1px solid silver;
                    box-shadow: 3px 3px 3px grey;
                    width: 20%;
                }

                div.stats div.tile div.header {
                    font-weight: bold;
                    border-bottom: 1px solid silver;
                    margin-bottom: 5px;
                }

                div.stats div.tile div.earnings 
                div.stats div.tile div.sales{
                    margin-bottom: 5px;
                }

                div.stats div.tile div.label,
                div.stats div.tile div.value {
                    display: inline-block;
                }

                div.stats div.tile div.label {
                    width: 40%;
                    max-width: 150px;
                }

                div.container_graph {
                    background-color: white; 
                    position: relative;
                    border: 1px solid silver;
                    box-shadow: 3px 3px 3px grey;
                }

                div.container_graph div.period {
                    position: absolute;
                    top: 24px;
                    left: 20px;
                }

                div.container_graph div.period span {
                    font-weight: bold;
                }
            </style> 
            <?php
            $payment_values = $this->controller->get_total_earnings_sales();
            ?>  
            <div class="stats clearfix">
                <?php
                foreach ($payment_values as $key => $value) {
                    ?>
                    <div class="tile clearfix">
                        <div class="header"><?php echo esc_html($value->L); ?></div>
                        <div class="earnings">
                            <div class="label"><?php echo __('Earnings', 'wpfront-paddle-gateway') . ': '; ?></div>
                            <div class="value"><?php echo esc_html($value->C . number_format($value->E, 2)); ?></div>
                        </div>
                        <div class="sales">
                            <div class="label"><?php echo __('Sales', 'wpfront-paddle-gateway') . ': '; ?></div>
                            <div class="value"><?php echo esc_html($value->S); ?></div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="clear"></div>
            </div>
            <div class="container_graph">
                <form method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr($this->controller->get_menu_slug()); ?>" />
                    <div class="period">
                        <span><?php echo __('Period', 'wpfront-paddle-gateway'). ': '; ?></span>
                        <?php
                        $selected = '';
                        if (!empty($_GET['period'])) {
                            $selected = sanitize_text_field($_GET['period']);
                        }
                        ?>
                        <select name="period">
                            <option value="last_30_days" <?php echo $selected === 'last_30_days' ? 'selected' : ''; ?>><?php echo __('Last 30 Days', 'wpfront-paddle-gateway'); ?></option>
                            <option value="this_month" <?php echo $selected === 'this_month' ? 'selected' : ''; ?>><?php echo __('This Month', 'wpfront-paddle-gateway'); ?></option>
                            <option value="previous_month" <?php echo $selected === 'previous_month' ? 'selected' : ''; ?>><?php echo __('Previous Month', 'wpfront-paddle-gateway'); ?></option>
                            <option value="this_year" <?php echo $selected === 'this_year' ? 'selected' : ''; ?>><?php echo __('This Year', 'wpfront-paddle-gateway'); ?></option>
                            <option value="previous_year" <?php echo $selected === 'previous_year' ? 'selected' : '' ?>><?php echo __('Previous Year', 'wpfront-paddle-gateway'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php echo __('Apply', 'wpfront-paddle-gateway'); ?>">
                    </div>
                </form>                               
                <canvas id="paddle_chart" height="100"></canvas>
            </div>
            <script type = "text/javascript">
                (function () {
                    jQuery(function () {
                        const label1 = <?php echo wp_json_encode(__('Earnings', 'wpfront-paddle-gateway')); ?>; 
                        const label2 = <?php echo wp_json_encode(__('Sales', 'wpfront-paddle-gateway')); ?>;
                        const title = <?php echo wp_json_encode(__('Earnings & Sales Over Time', 'wpfront-paddle-gateway')); ?>;

                        const graph_data = <?php echo wp_json_encode($this->controller->get_graph_data()); ?>; 

                        const data = {
                            labels: graph_data,
                            datasets: [
                                {
                                    label: label1,
                                    data: graph_data,
                                    tension: 0.1,
                                    backgroundColor: 'rgba(245, 66, 66, 1)',
                                    borderColor: 'rgba(245, 108, 66, .5)',
                                    yAxisID: 'y',
                                    parsing: {
                                        yAxisKey: 'E'
                                    }
                                },
                                {
                                    label: label2,
                                    data: graph_data,
                                    tension: 0.1,
                                    backgroundColor: 'rgba(66, 164, 245, 1)',
                                    borderColor: 'rgba(66, 164, 245, .5)',
                                    yAxisID: 'y1',
                                    parsing: {
                                        yAxisKey: 'S'
                                    }
                                }
                            ]
                        };

                        var ctx = document.getElementById('paddle_chart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: data,
                            options: {
                                layout: {
                                    padding: 20
                                },
                                interaction: {
                                    intersect: false,
                                    mode: 'index',
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            callback: function (value, index, values) {
                                                return this.getLabelForValue(value).D;
                                            }
                                        }
                                    },
                                    y: {
                                        type: 'linear',
                                        display: 'auto',
                                        position: 'left',
                                        min: 0,
                                        suggestedMax: 10,
                                        title: {
                                            display: true,
                                            text: label1,
                                            color: 'rgb(245, 66, 66)'
                                        }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: 'auto',
                                        position: 'right',
                                        min: 0,
                                        suggestedMax: 10,
                                        grid: {
                                            drawOnChartArea: false
                                        },
                                        title: {
                                            display: true,
                                            text: label2,
                                            color: 'rgb(66, 164, 245)'
                                        }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        position: 'nearest',
                                        callbacks: {
                                            title: function (objs) {
                                                return objs[0].raw.D;
                                            },
                                            label: function (context) {
                                                var label = context.dataset.label + ': ';
                                                
                                                if(context.dataset.yAxisID === 'y') {
                                                    label += context.raw.C;
                                                }
                                                
                                                label += context.parsed.y;
                                                
                                                return label;
                                            }
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: title
                                    }
                                }
                            }
                        });
                    });
                })();
            </script>
            <?php
        }

    }

}
