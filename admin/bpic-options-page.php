<?php
    /**
     *
     * Admin area for BuddyPress Instant Chat plugin
     *
     */

    if (!defined('ABSPATH')) exit; // Exit if accessed directly

    if (!current_user_can('manage_options')) {
        wp_die( __('Unfortunately you must be an admin to make changes on this page', 'bpic') );
    }
?>
<div class="wrap">
    <h2><?php _e('BuddyPress Instant Chat', 'bpic'); ?></h2>

    <form method="post" action="options.php">
        <?php settings_fields($this->plugin_name); ?>
        <?php do_settings_sections(__FILE__); ?>
        <?php submit_button(); ?>
    </form>
</div>
