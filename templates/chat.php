<?php
    if(have_posts()){
        while(have_posts()){
            the_post();
            if(get_the_content() !== '[bp-instant-chat]'){
                do_shortcode('[bp-instant-chat]');
            }else{
                the_content();
            }
        }
    }
?>
