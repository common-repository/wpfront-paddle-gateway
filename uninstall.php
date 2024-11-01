<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once dirname(__FILE__) . '/includes/class-wpfront-paddle-gateway.php';

if (is_multisite()) {
    global $wpdb;
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    $current_blog_id = get_current_blog_id();

    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);

        wpfront_paddle_uninstall();
    }

    switch_to_blog($current_blog_id);
} else {
    wpfront_paddle_uninstall();
}

wp_cache_flush();

function wpfront_paddle_uninstall() {
    WPFront\Paddle\Entities\Settings_Entity::uninstall();
    WPFront\Paddle\Entities\Payments_Entity::uninstall();
    WPFront\Paddle\Entities\Paylink_Entity::uninstall();
    
    if(class_exists('\WPFront\Paddle\Pro\Entities\Subscription_Plan_Entity')) {
        WPFront\Paddle\Pro\Entities\Subscription_Plan_Entity::uninstall();
    }
}
