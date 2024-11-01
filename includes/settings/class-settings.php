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

namespace WPFront\Paddle;

if (!defined('ABSPATH')) {
    exit();
}

require __DIR__ . '/class-settings-entity.php';
require __DIR__ . '/class-admin-settings.php';

use WPFront\Paddle\Entities\Settings_Entity;

if (!class_exists('\WPFront\Paddle\Settings')) {

    class Settings { //TODO: link to plugins page

        protected static $instance = null;
        
        protected function __construct() {
        }

        /**
         * Returns class instance.
         *
         * @return Settings
         */
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new Settings();
            }

            return self::$instance;
        }
        
        public function init($main) {
            new Admin\Admin_Settings($main);
        }
        
        public function update_setting($name, $value) {
            $entity = new Settings_Entity();
            $entity->settings_name = $name;
            $entity->settings_value = $value;
            $entity->update();
        }
        
        public function get_setting($name) {
            $entity = new Settings_Entity();
            return $entity->get($name);
        }
        
        public function delete_setting($name) {
            $entity = new Settings_Entity();
            return $entity->delete($name);
        }

    }

}