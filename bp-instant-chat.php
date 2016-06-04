<?php
    /**
    * Plugin Name: BuddyPress Instant Chat
    * Plugin URI: http://iamrichardphelps.com/
    * Description: Instant chat plugin for BuddyPress alowing user to connect and talk in real time.
    * Tags: buddypress
    * Version: 1.0
    * Author: Richard Phelps
    * Author URI: http://iamrichardphelps.com/
    * License: GPL12
    */

    // TO DO:
    // Admin settings (avatar height, avatar width, name display)
    // Allow for message load timout to be set in admin (post launch)
    // Get conversations code (see line 167 of this file and line 36 of the chat page template) (post launch)


    if (!class_exists('BPIC'))
    {
        class BPIC
        {
            public $conversation_table;
            public $message_table;
            private $charset_collate;
            public $conversations = array();

            public function __construct()
            {
                global $wpdb;

                $this->conversation_table = $wpdb->prefix . 'bpic_conversations';
                $this->message_table = $wpdb->prefix . 'bpic_messages';

                if ($wpdb) {
                    $this->charset_collate = $wpdb->get_charset_collate();
                }

                // BuddyPress Hooks
                add_action( 'bp_init', array($this, 'init') );

                // Admin Hooks
                add_action( 'admin_init', array($this, 'admin_init') );
                add_action( 'admin_notices', array($this, 'admin_notices') );

                // Filters
                add_filter( 'page_template', array($this, 'set_page_template') );
                add_filter( 'query_vars', array($this, 'set_query_vars') );

                // Fix for headers already sent message when trying to use wp_redirect
                add_action( 'init', array($this, 'output_buffering_start') );
                add_action( 'wp_footer', array($this, 'output_buffering_end') );
            }

            public function init()
            {
                global $wpdb;

                // Create statement for conversation table
                $sql_c = "CREATE TABLE IF NOT EXISTS " . $this->conversation_table . " (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    user_one int(11) NOT NULL,
                    user_two int(11) NOT NULL,
                    UNIQUE KEY id (id)
                ) " . $this->charset_collate . ";";
                $wpdb->query($sql_c);

                // Create statement for messages table
                $sql_m = "CREATE TABLE IF NOT EXISTS " . $this->message_table . " (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    conversation_id int(11) NOT NULL,
                    message_from int(11) NOT NULL,
                    message_to int(11) NOT NULL,
                    message longtext NOT NULL,
                    timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    status ENUM('0','1') DEFAULT '0' NOT NULL,
                    UNIQUE KEY id (id)
                ) " . $this->charset_collate . ";";
                $wpdb->query($sql_m);

                // Create chat page
                $chat_page = array(
                    'post_title' => 'Chat',
                    'post_content' => '',
                	'post_status' => 'publish',
                	'post_type' => 'page',
                	'post_author' => $wpdb->user_ID,
                	'post_date' => date('Y-m-d G:i:s')
                );

                if (get_page_by_title('Chat') == NULL) {
                    wp_insert_post($chat_page);
                }

                // Enqueue styles / scripts
                wp_enqueue_style('bpic-style', plugin_dir_url( __FILE__ ) . '/css/bpic-frontend-style.css', array(), '1.0.0');

                update_option('bpic_avatar_width', 50);
                update_option('bpic_avatar_height', 50);
                update_option('bpic_name_display', 'user_nicename');
            }

            public function admin_init()
            {
                // Check if BuddyPress 2.0 is installed.
                $buddypress_version = 0;
                if (function_exists('is_plugin_active') && is_plugin_active('buddypress/bp-loader.php')) {
                    $data = get_file_data(WP_PLUGIN_DIR . '/buddypress/bp-loader.php', array('Version'));
                    if (isset($data) && count($data) > 0 && $data[0] != '') {
                        $buddypress_version = (float)$data[0];
                    }
                }

                if ($buddypress_version < 2) {
                    $admin_notices = get_option('bpic_notices');
                    $admin_notices[] = __('BuddyPress Instant Chat requires <b>BuddyPress 2.0</b>, please ensure that BuddyPress is installed and up to date.', 'bpic');
                    update_option('bpic_notices', $admin_notices);
                }
            }

            public function admin_notices()
            {
                // Setup admin notices
                $admin_notices = get_option('bpic_notices');
                if ($admin_notices) {
                    foreach ($admin_notices as $admin_notice)
                    {
                        echo '<div class="error"><p>' . $admin_notice . '</p></div>';
                    }
                    delete_option('bpic_notices');
                }
            }

            public function set_page_template()
            {
                // Set page template for conversations page
                if (is_page('chat')) {
                    $page_template = dirname( __FILE__ ) . '/templates/chat.php';
                }

                return $page_template;
            }

            public function set_query_vars($vars)
            {
                // Set custom WordPress query vars
                $vars[] = 'sc';
                $vars[] = 'cid';
                $vars[] = 'action';

                return $vars;
            }

            public function check_loggedin()
            {
                if (!is_user_logged_in()) {
                    wp_redirect(site_url());
                    exit;
                }
            }

            public function get_conversations()
            {
                global $wpdb;

                $conversation_count = $wpdb->get_results("SELECT COUNT(*) AS count FROM $this->conversation_table WHERE user_one = '" . bp_loggedin_user_id() . "' OR user_two = '" . bp_loggedin_user_id() . "'");
                $conversations = $wpdb->get_results("SELECT * FROM $this->conversation_table WHERE user_one = '" . bp_loggedin_user_id() . "' OR user_two = '" . bp_loggedin_user_id() . "'");

                if($conversation_count[0]->count !== 0){
                    // NEED TO REPLACE LINE BELOW WITH ACTUAL CONVERSATIONS ARRAY (LAST MESSAGE ID, USER TWO ETC.)
                    return true;
                }
            }

            public function set_url($get_variable)
            {
                if (get_query_var('page_id')) {
                    return get_permalink( get_page_by_title('Chat') ) . '&' . $get_variable . '=';
                } else {
                    return get_permalink( get_page_by_title('Chat') ) . '?' . $get_variable . '=';
                }
            }

            public function user_search($post)
            {
                global $wpdb;

                $query = $post['bpic_user'];
                // Search for users
                $users = $wpdb->get_results("SELECT ID, display_name, user_nicename
                    FROM wp_users
                    WHERE (display_name LIKE '%$query%' OR user_nicename LIKE '%$query%')
                    AND ID != '" . bp_loggedin_user_id() . "'
                ");

                if ($users) {
                    foreach ($users as $user) {
                        $loggedin_user = bp_loggedin_user_id();
                        $check_conversation = $wpdb->get_results("SELECT COUNT(*) AS count
                            FROM $this->conversation_table
                            WHERE (user_one = '$loggedin_user' AND user_two = '$user->ID')
                            OR (user_one = '$user->ID' AND user_two = '$loggedin_user')
                        ");

                        if ($check_conversation[0]->count == '0') {
                            echo '<a href="#" class="start-chat" user-id="' . $user->ID . '"><p>' . __('Start chat with', 'bpic') . ' ' .  $user->display_name . ' (' . $user->user_nicename . ')</p></a>';
                        } else {
                            echo '<a href="#" class="continue-chat" user-id="' . $user->ID . '"><p>' . __('Continue chat with', 'bpic') . ' ' .  $user->display_name . ' (' . $user->user_nicename . ')</p></a>';
                        }
                    }
                } else {
                    _e('<p>Sorry but we couldn\'t find any users by that name!</p>', 'bpic');
                }

                ?>
                    <script>
                        (function($){
                            $('.start-chat').click(function(){
                                var user = $(this).attr('user-id');
                                window.location.assign('<?php echo $this->set_url("sc"); ?>' + user);
                            });
                        })(jQuery);
                    </script>
                    <script>
                        (function($){
                            $('.continue-chat').click(function(){
                                var user = $(this).attr('user-id');
                                window.location.assign('<?php echo $this->set_url("cid"); ?>' + user);
                            });
                        })(jQuery);
                    </script>
                <?php
            }

            public function output_buffering_start()
            {
                ob_start();
            }

            public function output_buffering_end()
            {
                ob_end_flush();
            }

            public function start_conversation($user_one, $user_two)
            {
                global $wpdb;

                $check_conversations = $wpdb->get_results("SELECT COUNT(*) AS count FROM " . $this->conversation_table . " WHERE user_one = '$user_one' AND user_two = '$user_two'");

                if ($check_conversations[0]->count == 0) {
                    $wpdb->insert($this->conversation_table, array('user_one' => $user_one, 'user_two' => $user_two));
                }
                $conversation = $wpdb->get_results("SELECT id FROM " . $this->conversation_table . " WHERE user_one = '$user_one' AND user_two = '$user_two'");

                $url = $this->set_url('cid');

                // Take user to the newly created chat
                wp_redirect($url . $conversation[0]->id);
                exit;
            }

            public function set_status()
            {
                global $wpdb;

                // Set all messages to logged in user to read status
                $wpdb->update($this->message_table, array(
                    'status' => '1'
                ), array(
                    'message_to' => bp_loggedin_user_id()
                ));
            }

            public function retrieve_messages($cid)
            {
                global $wpdb;

                $this->set_status();

                $conversation = $wpdb->get_results("SELECT * FROM " . $this->conversation_table . " WHERE id = '$cid'");

                // Check user is a part of the conversation otherwise return 'error_1' so jquuery will redirect user
                if (bp_loggedin_user_id() == $conversation[0]->user_one || bp_loggedin_user_id() == $conversation[0]->user_two) {
                    $messages = $wpdb->get_results("SELECT * FROM " . $this->message_table . " WHERE conversation_id = '$cid' ORDER BY timestamp DESC");

                    if (!empty($messages)) {
                        foreach($messages as $message)
                        {
                            $avatar_args = array(
                                'item_id' => $message->message_from,
                                'type' => 'thumbnail',
                                'class' => 'bpic-message-user-avatar',
                                'width' => get_option('bpic_avatar_width'),
                                'height' => get_option('bpic_avatar_height')
                            );

                            $user_from = get_userdata($message->message_from);

                            $name_display = get_option('bpic_name_display');

                            if ($message->status == '0' && $message->message_from == bp_loggedin_user_id()) {
                                $status = __('Delivered', 'bpic');
                            } else if ($message->status == '1' && $message->message_from == bp_loggedin_user_id()) {
                                $status = __('Read', 'bpic');
                            } else {
                                $status = '';
                            }

                            // Return all messages for conversation
                            ?>
                                <div class="bpic-message-container">
                                    <?php echo bp_core_fetch_avatar($avatar_args); ?>
                                    <p class="bpic-message-display-name"><?php echo $user_from->$name_display; ?></p>
                                    <p class="bpic-message"><?php echo nl2br($message->message); ?></p>
                                    <span class="bpic-message-status"><?php echo $status; ?></span>
                                </div>
                            <?php
                        }
                    } else {
                        ?>
                            <p class="bpic-text-center">
                                <?php _e("There're currently no messages associated with this chat!", "bpic"); ?>
                            </p>
                        <?php
                    }
                } else {
                    echo 'error_1';
                    exit;
                }
            }

            public function insert_message($cid, $post)
            {
                global $wpdb;

                $message = $wpdb->escape($post['message']);

                $conversation = $wpdb->get_results("SELECT user_one, user_two FROM " . $this->conversation_table . " WHERE id = '$cid'");
                if ($conversation[0]->user_one == bp_loggedin_user_id()) {
                    $message_to = $conversation[0]->user_two;
                } else {
                    $message_to = $conversation[0]->user_one;
                }

                if (!empty($message)) {
                    $wpdb->insert($this->message_table, array(
                        'conversation_id' => $cid,
                        'message_from' => bp_loggedin_user_id(),
                        'message_to' => $message_to,
                        'message' => $message,
                        'timestamp' => date('Y-m-d G:i:s'),
                        'status' => 0
                    ));

                    $avatar_args = array(
                        'item_id' => bp_loggedin_user_id(),
                        'type' => 'thumbnail',
                        'class' => 'bpic-message-user-avatar',
                        'width' => get_option('bpic_avatar_width'),
                        'height' => get_option('bpic_avatar_height')
                    );

                    $name_display = get_option('bpic_name_display');

                    // Return new message into the chat
                    ?>
                        <div class="bpic-message-container">
                            <?php echo bp_core_fetch_avatar($avatar_args); ?>
                            <p class="bpic-message-display-name"><?php echo get_userdata( bp_loggedin_user_id() )->$name_display; ?></p>
                            <p class="bpic-message"><?php echo nl2br($message); ?></p>
                            <span class="bpic-message-status"><?php _e('Delivered', 'bpic'); ?></span>
                        </div>
                    <?php
                }
            }
        }
    }

    if (class_exists('BPIC')) {
        $bpic = new BPIC();
    }
?>
