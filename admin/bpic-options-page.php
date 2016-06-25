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
    $bpic_avatar_height = get_option($this->plugin_prefix . 'avatar_height');

    if ($_POST['submit'] && $_POST['form'] == $this->plugin_prefix . 'options') {
        $this->options_validate($_POST);
    }
?>
<div class="wrap">
    <h2><?php _e('BuddyPress Instant Chat', 'bpic'); ?></h2>

    <?php
        if (!$_GET['view'] || $_GET['view'] == 'settings') {
            $tab_1_class = 'nav-tab-active';
            $tab_2_class = '';
        } else {
            $tab_1_class = '';
            $tab_2_class = 'nav-tab-active';
        }
    ?>
    <h2 class="nav-tab-wrapper">
    	<a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=<?php echo $this->plugin_name; ?>&view=settings" class="nav-tab <?php echo $tab_1_class; ?>"><?php _e('Settings', 'bpic'); ?></a>
    	<a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=<?php echo $this->plugin_name; ?>&view=msg_control" class="nav-tab <?php echo $tab_2_class; ?>"><?php _e('Message Control', 'bpic'); ?></a>
    </h2>

    <?php if (!$_GET['view'] || $_GET['view'] == 'settings') { ?>
        <form method="post" action="<?php echo site_url(); ?>/wp-admin/admin.php?page=<?php echo $this->plugin_name; ?>">
            <input type="hidden" name="form" value="<?php echo $this->plugin_prefix; ?>options">
            <h2><?php _e('Settings', 'bpic'); ?></h2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="avatar_width"><?php _e('Message Avatar Width', 'bpic'); ?></label>
                        </th>
                        <td>
                            <?php if (!$_POST['avatar_width']) { ?>
                                <input type="number" name="avatar_width" id="avatar_width" value="<?php echo $this->int_value($bpic_avatar_width); ?>" class="all-options" />
                            <?php } else { ?>
                                <input type="number" name="avatar_width" id="avatar_width" value="<?php echo $this->int_value($_POST['avatar_width']); ?>" class="all-options" />
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="avatar_height"><?php _e('Message Avatar Height', 'bpic'); ?></label>
                        </th>
                        <td>
                            <?php if (!$_POST['avatar_height']) { ?>
                                <input type="number" name="avatar_height" id="avatar_height" value="<?php echo $this->int_value($bpic_avatar_height); ?>" class="all-options" />
                            <?php } else { ?>
                                <input type="number" name="avatar_height" id="avatar_height" value="<?php echo $this->int_value($_POST['avatar_height']); ?>" class="all-options" />
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="name_display"><?php _e('Message Name Display', 'bpic'); ?></label>
                        </th>
                        <td>
                            <select name="name_display" id="name_display">
                                <option value="user_login" <?php echo $this->option_check($this->plugin_prefix . 'name_display', 'user_login'); ?>><?php _e('Username', 'bpic'); ?></option>
                                <option value="user_email" <?php echo $this->option_check($this->plugin_prefix . 'name_display', 'user_email'); ?>><?php _e('Email Address', 'bpic'); ?></option>
                                <option value="display_name" <?php echo $this->option_check($this->plugin_prefix . 'name_display', 'display_name'); ?>><?php _e('Display Name', 'bpic'); ?></option>
                                <option value="user_firstname" <?php echo $this->option_check($this->plugin_prefix . 'name_display', 'user_firstname'); ?>><?php _e('First Name', 'bpic'); ?></option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    <?php } else { ?>
        <form method="post" action="<?php echo site_url(); ?>/wp-admin/admin.php?page=<?php echo $this->plugin_name; ?>&view=msg_control">
            <input type="hidden" name="form" value="<?php echo $this->plugin_prefix; ?>msg_control">
            <h2><?php _e('Message Control', 'bpic'); ?></h2>
            <div class="bpic-left-msg-control-search">
                <label for="user_one"><b><?php _e('User 1', 'bpic'); ?></b></label>
                <input type="text" class="regular-text" name="user_one" id="user_one" placeholder="<?php _e('Search users by their ID, Username or Email'); ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Search users by their ID, Username or Email'); ?>'" <?php if($_POST['user_one']){ ?>value="<?php echo esc_html($_POST['user_one']); ?>"<?php } ?> />
            </div>
            <div class="bpic-right-msg-control-search">
                <label for="user_two"><b><?php _e('User 2', 'bpic'); ?></b></label>
                <input type="text" class="regular-text" name="user_two" id="user_two" placeholder="<?php _e('Search users by their ID, Username or Email'); ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Search users by their ID, Username or Email'); ?>'" <?php if($_POST['user_two']){ ?>value="<?php echo esc_html($_POST['user_two']); ?>"<?php } ?> />
            </div>
            <div class="bpic-submit-msg-control-search"><?php submit_button(); ?></div>
            <?php
                if ($_POST['submit'] && $_POST['form'] == $this->plugin_prefix . 'msg_control') {
                    $this->message_control($_POST);
                }
            ?>
        </form>
    <?php } ?>
</div>
