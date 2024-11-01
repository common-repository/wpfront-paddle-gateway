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


if (!class_exists('\WPFront\Paddle\Admin\Admin_Paylinks_Delete_View')) {

    class Admin_Paylinks_Delete_View extends \WPFront\Paddle\Template_Base {

        /**
         *
         * @var Admin_Paylinks
         */
        protected $controller;
        protected $paylink_data;

        public function __construct($controller, $data) {
            $this->controller = $controller;
            $this->paylink_data = $data;
        }

        public function view() {
            ?>
            <div class="wrap paddle-admin-paylinks">
                <?php $this->title(); ?>
                <form id="form-paddle-admin-paylink" method='post' action="<?php echo esc_attr($this->controller->get_delete_url(null)); ?>">
                    <ol>
                        <?php $this->paylink_display(); ?>
                    </ol>
                    <input type="hidden" name="action" value="delete" />
                    <?php wp_nonce_field('bulk-action-delete-paylink'); ?>
                    <?php submit_button(__('Confirm Delete', 'wpfront-paddle-gateway'), 'button-secondary'); ?>
                </form>
            </div>
            <?php
        }

        protected function title() {
            ?>
            <h2>
                <?php echo __('Delete Paylinks', 'wpfront-paddle-gateway'); ?>
                <p><?php echo __('The following paylinks will be deleted.', 'wpfront-paddle-gateway'); ?></p>
            </h2>
            <?php
        }

        protected function paylink_display() {
            foreach ($this->paylink_data as $entity) {
                printf('<li>%s[%s]</li>', esc_html($entity->label), esc_html($entity->name));
                printf('<input type="hidden" name="paylinks[]" value="%s" />', esc_attr($entity->id));
            }
        }

        public function set_help() {
            $tabs = array(
                array(
                    'id' => 'overview',
                    'title' => __('Overview', 'wpfront-user-role-editor'),
                    'content' => '<p>'
                    . __('This screen allows you to delete paylinks from your WordPress site.', 'wpfront-paddle-gateway')
                    . '</p>'
                    . '<p>'
                    . __('Use the Paylinks List screen to select the paylinks you want to delete. You can delete individual paylinks using the Delete row action link or delete multiple paylinks at the same time using the bulk action.', 'wpfront-paddle-gateway')
                    . '</p>'
                )
            );

            $sidebar = array(
                array(
                    __('Documentation on Paylinks Delete', 'wpfront-paddle-gateway'),
                    'paylinks/'
                )
            );

            $this->set_help_tab($tabs, $sidebar);
        }

    }

}