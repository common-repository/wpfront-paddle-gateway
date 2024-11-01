<?php
 
/*
 * Plugin Name: Easy Digital Downloads - Paddle Gateway
 * Plugin URI: http://wpfront.com/wordpress-paddle-gateway/ 
 * Description: Integrate your WordPress site or Easy Digital Downloads store with Paddle payment gateway.
 * Version: 1.1
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: Syam Mohan
 * Author URI: http://wpfront.com
 * License: GPL v3 
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wpfront-paddle-gateway
 * Domain Path: /languages
 */

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

require dirname(__FILE__) . '/includes/class-wpfront-paddle-gateway.php';

use WPFront\Paddle\WPFront_Paddle_Gateway;

WPFront_Paddle_Gateway::instance()->init(__FILE__);