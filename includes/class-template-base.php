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

if (!class_exists('\WPFront\Paddle\Template_Base')) {

    /**
     * Template base class
     *
     * @author Syam Mohan <syam@wpfront.com>
     * @copyright 2021 WPFront.com
     */
    abstract class Template_Base {

        abstract public function set_help();
        
        /**
         * Sets the help tab of current screen. Returns whether it was successful.
         * 
         * @param array $tabs
         * @param array $sidebar
         * @return boolean
         */
        protected function set_help_tab($tabs, $sidebar) {
            $screen = get_current_screen();
            
            if(empty($screen)) {
                return false;
            }

            if(!empty($tabs)) {
                foreach ($tabs as $value) {
                    $screen->add_help_tab($value);
                }
            }

            if (!empty($sidebar)) {
                $s = '<p><strong>' . __('Links', 'wpfront-paddle-gateway') . ':</strong></p>';

                foreach ($sidebar as $value) {
                    $s .= '<p><a target="_blank" href="https://wpfront.com/wordpress-plugins/wordpress-paddle-gateway/' . $value[1] . '">' . $value[0] . '</a></p>';
                }
                
                $s .= '<p><a target="_blank" href="https://wordpress.org/plugins/wpfront-paddle-gateway/#faq">' . __('FAQ', 'wpfront-paddle-gateway') . '</a></p>';
                $s .= '<p><a target="_blank" href="https://wordpress.org/support/plugin/wpfront-paddle-gateway/">' . __('Support', 'wpfront-paddle-gateway') . '</a></p>';
                $s .= '<p><a target="_blank" href="https://wordpress.org/support/plugin/wpfront-paddle-gateway/reviews/">' . __('Review', 'wpfront-paddle-gateway') . '</a></p>';
                
                $s .= '<p><a target="_blank" href="https://wpfront.com/contact/">' . __('Contact', 'wpfront-paddle-gateway') . '</a></p>';

                $screen->set_help_sidebar($s);
            }
            
            return true;
        }
    
    }

}

