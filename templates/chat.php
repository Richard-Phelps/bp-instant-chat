<?php
    $bpic = new BPIC;

    $bpic->check_loggedin();

    // If ajax call to retrieve messages
    if(get_query_var('action') && get_query_var('action') == 'retrieve'){
        $bpic->retrieve_messages(get_query_var('cid'));
        die;
    }

    // If ajax call to send new message
    if(get_query_var('action') && get_query_var('action') == 'insert'){
        $bpic->insert_message(get_query_var('cid'), $_POST);
        die;
    }

    get_header();

    $conversations = $bpic->get_conversations();

    if(!get_query_var('sc') && !get_query_var('cid')){
        ?>
            <form method="POST">
                <input type="text" name="bpic_user" class="bpic-user-search-input" value="<?php echo $_POST['bpic_user']; ?>" placeholder="<?php _e('Search for user by their name', 'bpic'); ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Search for user by their name', 'bpic'); ?>'">
                <input type="submit" name="bpic_search" class="bpic-user-search-button" value="<?php _e('Search', 'bpic'); ?>">
            </form>
        <?php
    }

    if(!$_POST){
        // Check if user has started conversation and if not then continue
        if(!get_query_var('sc')){
            // Check if user has selected a chat and if not then continue
            if(!get_query_var('cid')){
                if($conversations){
                    // ENTER CODE TO GET CONVERSATIONS
                }else{
                    _e('<p>You haven\'t started any conversations yet.</p>', 'bpic');
                }
            }else{
                ?>
                    <form class="bpic-message-form" id="bpic_message_form">
                        <textarea name="message" class="bpic-textarea" id="bpic_message" placeholder="<?php _e('Enter message to send...', 'bpic'); ?>" onfocus="this.placeholder=''" onblur="this.placeholder='<?php _e('Enter message to send...', 'bpic'); ?>'"></textarea>
                        <input type="submit" class="bpic-submit-message" value="<?php _e('Send Message', 'bpic'); ?>">
                    </form>
                    <div class="bpic-error-container">&nbsp;</div>
                    <div class="chat-container" id="chat_container">
                        <div class="bpic-text-center">
                            <img src="<?php echo str_replace('/templates', '', plugin_dir_url( __FILE__ )) . '/images/loading_spinner.gif'; ?>" class="bpic-loading-spinner" alt="<?php _e('Loading', 'bpic'); ?>">
                        </div>
                    </div>
                    <script type="text/javascript">
                        (function($){
                        	var update_time = 2;
                           	var running = false;
                            var count_secs = 0;
                            function chat_update(){
                                if(count_secs == update_time){
                                    load_message();
                                }else{
                                    count_secs++;
                                }
                                if(running == true){
                                    setTimeout(chat_update, 1000);
                                }
                            }

                    		function load_message(){
                    			$.ajax({
                    				url: '<?php echo $bpic->set_url("action"); ?>retrieve&cid=<?php echo get_query_var("cid"); ?>',
                    				cache: false,
                    				success: function(data){
                                        if(data == 'error_1'){
                                            window.location.assign('<?php echo get_permalink( get_page_by_title("Chat") ); ?>');
                                        }else{
                                            $('#bpic_message_form').show();
                                            $('#chat_container').html(data);
                                        }
                						count_secs = 0;
                						setTimeout(load_message, 1000 * update_time);
                    				},
                    			});
                    		}

                            load_message();
                            running = true;

                			$('#bpic_message_form').submit(function(){
                                $('.bpic-error-container').html();
                                var cid = <?php echo get_query_var('cid'); ?>;
                                var message = $('#bpic_message').val();
                                if(message !== ''){
                    				$.post('<?php echo $bpic->set_url("action"); ?>insert&cid=' + cid, {message: message}, function(data){
                                        $('.bpic-no-messages').remove();
                                        $('#bpic_message').val('');
                    					$('#chat_container').prepend(data);
                    				});
                                }else{
                                    $('.bpic-error-container').html('<p class="bpic-error-message"><?php echo _e("You must enter some text before you can send your message"); ?></p>');
                                }
                				return false;
                			});
                        })(jQuery);
                    </script>
                <?php
            }
        }else{
            $bpic->start_conversation(bp_loggedin_user_id(), get_query_var('sc'));
            die;
        }
    }else{
        $bpic->user_search($_POST);
    }

    get_footer();
?>
