<?php
    /**
    * Plugin Name:  BuddyPress Instant Chat
    * Plugin URI:   http://iamrichardphelps.com/
    * Description:  Instant chat plugin for BuddyPress allowing user to connect and talk in real time.
    * Tags:         buddypress, chat, instant, messaging, communication, contact, users, plugin, page, AJAX, social, free
    * Version:      1.2
    * Author:       Richard Phelps
    * Author URI:   http://iamrichardphelps.com/
    * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
    * Text Domain:  bpic
    * Domain Path:  /languages
    * License:      GPLv2 or later
    */

    if (!class_exists('BPIC'))
    {
        class BPIC
        {
            public $plugin_name = 'bp-instant-chat';
            private $version = '1.2';
            public $conversation_table;
            public $message_table;
            private $charset_collate;
            public $conversations = array();
            public $plugin_prefix = 'bpic_';

            /**
        	 * Initialize the class.
        	 *
        	 * @since     1.0
        	 */
            public function __construct()
            {
                global $wpdb;

                $this->conversation_table = $wpdb->prefix . $this->plugin_prefix . 'conversations';
                $this->message_table = $wpdb->prefix . $this->plugin_prefix . 'messages';

                if ($wpdb) {
                    $this->charset_collate = $wpdb->get_charset_collate();
                }

                // BuddyPress Hooks
                add_action( 'bp_init', array($this, 'init') );

                // Admin Hooks
                add_action( 'admin_init', array($this, 'admin_init') );
                add_action( 'admin_notices', array($this, 'admin_init_error_notices') );
                add_action( 'admin_notices', array($this, 'admin_error_notice') );
                add_action( 'admin_notices', array($this, 'admin_success_notice') );
                add_action( 'admin_menu', array($this, 'add_options_page') );

                // Filters
                add_filter( 'page_template', array($this, 'set_page_template') );
                add_filter( 'query_vars', array($this, 'set_query_vars') );

                // Fix for headers already sent message when trying to use wp_redirect
                add_action( 'init', array($this, 'output_buffering_start') );
                add_action( 'wp_footer', array($this, 'output_buffering_end') );
            }

            /**
        	 * Setup everything when plugin is activated.
        	 *
        	 * @since     1.0
        	 */
            public function init()
            {
                require_once(ABSPATH . 'wp-includes/pluggable.php');

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
                wp_enqueue_style('bpic-frontend-style', plugin_dir_url( __FILE__ ) . '/css/bpic-frontend-style.css', array(), '1.0');
                wp_enqueue_style('bpic-admin-style', plugin_dir_url( __FILE__ ) . '/admin/css/bpic-admin-style.css', array(), '1.0');

                if(!get_option($this->plugin_prefix . 'avatar_width')){
                    update_option($this->plugin_prefix . 'avatar_width', 50);
                }

                if(!get_option($this->plugin_prefix . 'avatar_height')){
                    update_option($this->plugin_prefix . 'avatar_height', 50);
                }

                if(!get_option($this->plugin_prefix . 'name_display')){
                    update_option($this->plugin_prefix . 'name_display', 'user_login');
                }
            }

            /**
        	 * Checks to be done when admin is loaded.
        	 *
        	 * @since     1.0
        	 */
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
                    $admin_init_error_notices = get_option($this->plugin_prefix . 'error_notices');
                    $admin_init_error_notices[] = __('BuddyPress Instant Chat requires <b>BuddyPress 2.0</b>, please ensure that BuddyPress is installed and up to date.', 'bpic');
                    update_option($this->plugin_prefix . 'error_notices', $admin_init_error_notices);
                }
            }

            /**
        	 * Create the options page in admin.
        	 *
        	 * @since     1.0
        	 */
            public function add_options_page()
            {
                $bpic_title = __('BuddyPress Instant Chat', 'bpic');
                $bpic_capabilities = 'manage_options';
                $bpic_slug = $this->plugin_name;
                $bpic_icon = 'dashicons-format-chat';

                add_menu_page($bpic_title, $bpic_title, $bpic_capabilities, $bpic_slug, array($this, 'options_page'), $bpic_icon);
            }

            public function options_page()
            {
                include_once('admin/bpic-options-page.php');
            }

            /**
        	 * Display all admin init error notices.
        	 *
        	 * @since     1.0
        	 */
            public function admin_init_error_notices()
            {
                // Setup admin notices
                $admin_init_error_notices = get_option($this->plugin_prefix . 'error_notices');
                if ($admin_init_error_notices) {
                    foreach ($admin_init_error_notices as $admin_notice)
                    {
                        echo '<div class="notice notice-error"><p>' . $admin_notice . '</p></div>';
                    }
                    delete_option($this->plugin_prefix . 'error_notices');
                }
            }

            /**
        	 * Display an admin error notices.
        	 *
        	 * @since     1.0
             * @param     string     $message     The message to display as the error notice.
        	 */
            public function admin_error_notice($message)
            {
                if ($message) {
                    echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
                }
            }

            /**
        	 * Display an admin success notices.
        	 *
        	 * @since     1.0
             * @param     string     $message     The message to display as the success notice.
        	 */
            public function admin_success_notice($message)
            {
                if ($message) {
                    echo '<div class="notice notice-success is-dismissable"><p>' . $message . '</p></div>';
                }
            }

            /**
        	 * Create the template for the chat page.
        	 *
        	 * @since     1.0
        	 */
            public function set_page_template()
            {
                // Set page template for conversations page
                if (is_page('chat')) {
                    $page_template = dirname( __FILE__ ) . '/templates/chat.php';
                }

                return $page_template;
            }

            /**
        	 * Set all of the query vars to be used in the URL.
        	 *
        	 * @since     1.0
        	 */
            public function set_query_vars($vars)
            {
                // Set custom WordPress query vars
                $vars[] = 'sc';
                $vars[] = 'cid';
                $vars[] = 'action';

                return $vars;
            }

            /**
        	 * Make sure user is logged in.
        	 *
        	 * @since     1.0
        	 */
            public function check_loggedin()
            {
                if (!is_user_logged_in()) {
                    wp_redirect(site_url());
                    exit;
                }
            }

            /**
        	 * Return true is user has chats.
        	 *
        	 * @since     1.1
        	 */
            public function user_has_chats()
            {
                global $wpdb;

                $conversation_count = $wpdb->get_results("SELECT COUNT(*) AS count FROM $this->conversation_table WHERE user_one = '" . bp_loggedin_user_id() . "' OR user_two = '" . bp_loggedin_user_id() . "'");

                if ($conversation_count[0]->count !== 0) {
                    return true;
                } else {
                    return false;
                }
            }

            /**
             * Return information about a conversation.
             *
             * @since     1.1
             * @param     int     $conversation_id     The ID for the conversation to return information for.
             */
            public function get_conversation_data($conversation_id)
            {
                global $wpdb;

                $information = array();

                $check_user_one = $wpdb->get_results("SELECT * FROM $this->conversation_table WHERE id = '$conversation_id' AND user_one = '" . bp_loggedin_user_id() . "'");
                $check_user_two = $wpdb->get_results("SELECT * FROM $this->conversation_table WHERE id = '$conversation_id' AND user_two = '" . bp_loggedin_user_id() . "'");

                if (!empty($check_user_one)) {
                    $information['user_one'] = bp_loggedin_user_id();
                    $other_user = $information['user_two'] = $check_user_one[0]->user_two;
                } else if (!empty($check_user_two)) {
                    $other_user = $information['user_one'] = $check_user_two[0]->user_one;
                    $information['user_two'] = bp_loggedin_user_id();
                }

                $user = $wpdb->get_results("SELECT * FROM wp_users WHERE ID = '$other_user'");

                $name_display = get_option($this->plugin_prefix . 'name_display');
                if ($name_display == 'user_firstname') {
                    $information['other_user_name'] = get_user_meta($other_user, 'first_name', true);
                } else {
                    $information['other_user_name'] = $user[0]->$name_display;
                }

                return $information;
            }

            /**
        	 * Get all of the conversations and return as an array.
        	 *
        	 * @since     1.1
        	 */
            public function get_conversations()
            {
                global $wpdb;

                $conversations = $wpdb->get_results("SELECT * FROM $this->conversation_table WHERE user_one = '" . bp_loggedin_user_id() . "' OR user_two = '" . bp_loggedin_user_id() . "'");

                foreach ($conversations as $conversation)
                {
                    $conversation_id = $conversation->id;
                    $conversation_data = $this->get_conversation_data($conversation_id);

                    echo '<a href="#" class="continue-chat" conversation-id="' . $conversation_id . '"><p>' .  $conversation_data['other_user_name'] . '</p></a>';
                }
                ?>
                    <script>
                        (function($){
                            $('.continue-chat').click(function(){
                                var conversation = $(this).attr('conversation-id');
                                window.location.assign('<?php echo $this->set_url("cid"); ?>' + conversation);
                            });
                        })(jQuery);
                    </script>
                <?php
            }

            /**
        	 * Set the URL correctly for GET variables.
        	 *
        	 * @since     1.0
             * @param     string     $get_variable     The GET variable.
        	 */
            public function set_url($get_variable)
            {
                if (get_query_var('page_id')) {
                    return get_permalink( get_page_by_title('Chat') ) . '&' . $get_variable . '=';
                } else {
                    return get_permalink( get_page_by_title('Chat') ) . '?' . $get_variable . '=';
                }
            }

            /**
        	 * Search for users based on search query.
        	 *
        	 * @since     1.0
             * @param     array     $post     The post array.
        	 */
            public function user_search($post)
            {
                global $wpdb;

                $query = $post[$this->plugin_prefix . 'user'];
                // Search for users
                $users = $wpdb->get_results("SELECT *
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

                        $conversation_id = $wpdb->get_results("SELECT id
                            FROM $this->conversation_table
                            WHERE (user_one = '$loggedin_user' AND user_two = '$user->ID')
                            OR (user_one = '$user->ID' AND user_two = '$loggedin_user')
                        ");

                        $name_display = get_option($this->plugin_prefix . 'name_display');

                        if ($name_display == 'user_firstname') {
                            $user->$name_display = get_user_meta($user->ID, 'first_name', true);
                        }

                        if ($check_conversation[0]->count == '0') {
                            echo '<a href="#" class="start-chat" user-id="' . $user->ID . '"><p>' . __('Start chat with', 'bpic') . ' ' .  $user->$name_display . '</p></a>';
                        } else {
                            echo '<a href="#" class="continue-chat" conversation-id="' . $conversation_id[0]->id . '"><p>' . __('Continue chat with', 'bpic') . ' ' .  $user->$name_display . '</p></a>';
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
                                var conversation = $(this).attr('conversation-id');
                                window.location.assign('<?php echo $this->set_url("cid"); ?>' + conversation);
                            });
                        })(jQuery);
                    </script>
                <?php
            }

            /**
        	 * Start output buffering.
        	 *
        	 * @since     1.0
        	 */
            public function output_buffering_start()
            {
                ob_start();
            }

            /**
        	 * End output buffering.
        	 *
        	 * @since     1.0
        	 */
            public function output_buffering_end()
            {
                ob_end_flush();
            }

            /**
        	 * Insert a message into the database.
        	 *
        	 * @since     1.0
        	 * @param     int     $user_one     The id of user number 1 for the conversation.
        	 * @param     int     $user_two     The id of user number 2 for the conversation.
        	 */
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

            /**
        	 * Set messages to the correct status if they've been read.
        	 *
        	 * @since     1.0
        	 */
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

            /**
        	 * Retrieve messages from the database.
        	 *
        	 * @since     1.0
        	 * @param     int     $cid     The conversation id.
        	 */
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
                                'width' => get_option($this->plugin_prefix . 'avatar_width'),
                                'height' => get_option($this->plugin_prefix . 'avatar_height')
                            );

                            $user_from = get_userdata($message->message_from);

                            $name_display = get_option($this->plugin_prefix . 'name_display');

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
                            <p class="bpic-no-messages bpic-text-center">
                                <?php _e("There're currently no messages associated with this chat!", "bpic"); ?>
                            </p>
                        <?php
                    }
                } else {
                    echo 'error_1';
                    exit;
                }
            }

            /**
        	 * Insert a message into the database.
        	 *
        	 * @since     1.0
        	 * @param     int     $cid     The conversation id.
        	 * @param     array     $post     The post array.
        	 */
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
                        'width' => get_option($this->plugin_prefix . 'avatar_width'),
                        'height' => get_option($this->plugin_prefix . 'avatar_height')
                    );

                    $name_display = get_option($this->plugin_prefix . 'name_display');

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

            /**
        	 * Ensure a value being to displayed is an integer.
        	 *
        	 * @since     1.0
             * @param     int/string     $value     The value to check is an integer and return as an integer.
        	 */
            public function int_value($value)
            {
                if (is_numeric($value)) {
                    return intval($value);
                }
            }

            /**
        	 * Returns true if value is an integer and false if it's not
        	 *
        	 * @since     1.0
             * @param     int/string     $value     The value to check as an integer.
        	 */
            public function is_int($value)
            {
                if (is_numeric($value)) {
                    return true;
                } else {
                    return false;
                }
            }

            /**
        	 * Checks options to see if they're the value set in wp_options and return the selected html attribute if they are.
        	 *
        	 * @since     1.0
             * @param     string     $option     The option in the wp_options table to check.
             * @param     string     $value     The value that should be checked against.
        	 */
            public function option_check($option, $value)
            {
                $option_value = get_option($option);
                if ($option_value == $value) {
                    return 'selected="selected"';
                }
            }

            /**
        	 * Validate all values submitted in options.
        	 *
        	 * @since     1.0
             * @param     array     $post     The post array.
        	 */
            public function options_validate($post)
            {
                // Make sure no posted values are empty
                if ( !empty($post['avatar_width']) && !empty($post['avatar_height']) && !empty($post['name_display']) ) {

                    // Make sure avatr width is a number
                    if ( $this->is_int($post['avatar_width']) ) {

                        // Make sure avatar height is a number
                        if ( $this->is_int($post['avatar_height']) ) {

                            $this->save_options($post);

                        } else {
                            $this->admin_error_notice( __('Message avatar height must be a number!', 'bpic') );
                        }

                    } else {
                        $this->admin_error_notice( __('Message avatar width must be a number!', 'bpic') );
                    }

                } else {
                    $this->admin_error_notice( __('All of the fields must be populated!', 'bpic') );
                }
            }

            /**
        	 * Save the plugin options.
        	 *
        	 * @since     1.0
             * @param     array     $post     The post array.
        	 */
            public function save_options($post)
            {
                $name_options = array('user_login', 'user_email', 'display_name', 'user_firstname');

                $avatar_width = $this->int_value($_POST['avatar_width']);
                $avatar_height = $this->int_value($_POST['avatar_height']);
                $name_display = esc_html($_POST['name_display']);

                if (!in_array($name_display, $name_options)) {
                    $name_display = 'user_login';
                }

                $options_update_array = array(
                    $this->plugin_prefix . 'avatar_width' => $avatar_width,
                    $this->plugin_prefix . 'avatar_height' => $avatar_height,
                    $this->plugin_prefix . 'name_display' => $name_display
                );

                foreach($options_update_array as $option => $value)
                {
                    update_option($option, $value);
                }

                $this->admin_success_notice( __('Settings successfully saved.', 'bpic') );
            }

            /**
             * Checks if chat exists between 2 users and if it does it will return the chat id.
             *
             * @since     1.2
             * @param     string     $user_one     The first user to search for.
             * @param     string     $user_two     The second user to search for.
             */
            public function retrieve_chat($user_one, $user_two)
            {
                global $wpdb;

                $check_user_one = $wpdb->get_results("SELECT * FROM wp_users WHERE ID = '$user_one' OR user_login = '$user_one' OR user_email = '$user_one'");
                if (!$check_user_one[0]->ID) {
                    return __('Sorry but user 1 could not be found!', 'bpic');
                } else {
                    $user_one = $check_user_one[0]->ID;
                }

                $check_user_two = $wpdb->get_results("SELECT * FROM wp_users WHERE ID = '$user_two' OR user_login = '$user_two' OR user_email = '$user_two'");
                if (!$check_user_two[0]->ID) {
                    return __('Sorry but user 2 could not be found!', 'bpic');
                } else {
                    $user_two = $check_user_two[0]->ID;
                }

                if ($check_user_one[0]->ID && $check_user_one[0]->ID) {
                    $conversation = $wpdb->get_results("SELECT * FROM $this->conversation_table WHERE (user_one = '$user_one' AND user_two = '$user_two') OR (user_one = '$user_two' AND user_two = '$user_one')");
                    if (!empty($conversation)) {
                        return $conversation[0]->id;
                    }
                }
            }

            /**
             * Main method for message control functionality.
             *
             * @since     1.2
             * @param     string     $user_search_string     The string used to search for the user.
             */
            public function message_control_user_display($user_search_string)
            {
                if (get_user_by('ID', $user_search_string)) {
                    $user_id = $user_search_string;
                } else if (get_user_by('email', $user_search_string)) {
                    $user_id = get_user_by('email', $user_search_string)->ID;
                } else if (get_user_by('login', $user_search_string)) {
                    $user_id = get_user_by('login', $user_search_string)->ID;
                }

                return array('ID' => $user_id, 'display' => $user_search_string);
            }

            /**
             * Main method for message control functionality.
             *
             * @since     1.2
             * @param     array     $post     The post array.
             */
            public function message_control($post)
            {
                global $wpdb;

                $conversation_id = $this->retrieve_chat($post['user_one'], $post['user_two']);
                if (!empty($post['user_one']) && !empty($post['user_two'])) {
                    if (!$this->is_int($conversation_id)) {
                        ?>
                            <h2><?php echo $conversation_id; ?></h2>
                        <?php
                    } else {
                        // Check what was entered for searching users (ID, Username or Email)
                        $user_one = $this->message_control_user_display($post['user_one']);
                        $user_two = $this->message_control_user_display($post['user_two']);

                        $messages = $wpdb->get_results("SELECT * FROM $this->message_table WHERE conversation_id = '$conversation_id' ORDER BY `timestamp` DESC");
                        foreach($messages as $message)
                        {
                            if ($message->message_from == $user_one['ID']) {
                                $user_display = $user_one['display'];
                            } else if ($message->message_from == $user_two['ID']) {
                                $user_display = $user_two['display'];
                            }

                            ?>
                                <p class="bpic-message-control-message"><b><?php echo esc_html($user_display); ?>:</b> <?php echo esc_html($message->message); ?> (<?php echo $message->timestamp; ?>)</p>
                            <?php
                        }
                    }
                } else {
                    ?>
                        <h2><?php _e('You must select 2 users', 'bpic'); ?></h2>
                    <?php
                }
            }
        }
    }

    if (class_exists('BPIC')) {
        $bpic = new BPIC();
    }
?>
