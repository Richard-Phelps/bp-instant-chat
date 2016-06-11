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

    $bpic_avatar_width = get_option($this->plugin_prefix . 'avatar_width');
?>
<div class="wrap">
    <h2><?php _e('BuddyPress Instant Chat', 'bpic'); ?></h2>

    <form method="post" action="<?php echo site_url(); ?>/wp-admin/admin.php?page=<?php echo $this->plugin_name; ?>">
        <h2><?php _e('Settings', 'bpic'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="avatar_width"><?php _e('Avatar Width', 'bpic'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="avatar_width" id="avatar_width" value="<?php echo $this->int_display($bpic_avatar_width); ?>" class="all-options" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="avatar_height"><?php _e('Avatar Height', 'bpic'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="avatar_width" id="avatar_width" value="<?php echo $this->int_display($bpic_avatar_width); ?>" class="all-options" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
