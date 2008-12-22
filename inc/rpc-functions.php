<?php
function ICanLocalizeUpdateTextTranslation($args){
        global $wpdb;
        
        $user_login  = $args[0];
        $user_pass   = $args[1];
        
        $request_id = $args[2];
        $text = $args[3];
        
        if ( !get_option( 'enable_xmlrpc' ) ) {
            return sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php'));
        }
        if (!user_pass_ok($user_login, $user_pass)) {
            return 'Bad user/pass combination';
        }

        //get comment id
        $comment_id = $wpdb->get_var("SELECT comment_id FROM {$wpdb->prefix}comments_translation_requests WHERE request_id=".intval($request_id));
        if($comment_id){
            $wpdb->update($wpdb->comments, 
                array('comment_content'=>$text,'comment_approved'=>'1'),
                array('comment_ID'=>$comment_id)
            );           
            $ret = !mysql_errno();            
            $wpdb->update($wpdb->prefix.'comments_translation_requests',
                array('translated'=>1,'date_translated'=>date('Y-m-d H:i:s')),array('comment_id'=>$comment_id));                                       
        }else{
            $ret = 0;
        }                            
        return $ret;
}      

function ICanLocalizeTBValidate($args){
        
        $user_login  = $args[0];
        $user_pass   = $args[1];
        
        if ( !get_option( 'enable_xmlrpc' ) ) {
            return array('err_code'=>3, 'err_str'=>sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php')));
        }
        
        if (!user_pass_ok($user_login, $user_pass)) {
            return 2; //ERROR - user or password don’t match
        }
        
        $user = set_current_user( 0, $user_login );
        
        if($user->caps['editor'] || $user->caps['administrator']){
            return 0; //OK - user exists and has editor privileges (is editor or admin
        }else{
            return 1; //ERROR - user exists but cannot edit
        }
    
}      

function ICanLocalizeTBProcessPostAfterSubmission(){
    return 1;
}  

function ICanLocalizeSetLanguagesInfoDest($args){
        global $wpdb;
        
        
        $user_login  = $args[0];
        $user_pass   = $args[1];
        $post_id = $args[2];
        $link_info_for_language = $args[3];
        
        //$fh = fopen('debug.txt','w');
        //fwrite($fh, serialize($link_info_for_language));
        
        if ( !get_option( 'enable_xmlrpc' ) ) {
            return sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php'));
        }
        if (!user_pass_ok($user_login, $user_pass)) {
            return 'Bad user/pass combination';
        }
                
        if($post_id){            
            update_post_meta($post_id,'_ican_link_info_for_language',$link_info_for_language);
        }
        
        $document_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID='{$post_id}'");
        
        $translated_language = get_post_meta($post_id, '_ican_language',true);
        $original_language = get_post_meta($post_id, '_ican_from_language',true);

        $original_id = $link_info_for_language[$original_language]['id'];
        $translated_id = $link_info_for_language[$translated_language]['id'];
        
        $original_permalink = $link_info_for_language[$original_language]['permlink'];
        $permalink = $link_info_for_language[$translated_language]['permlink'];
        
        $original_blog_home = rtrim($link_info_for_language[$original_language]['blog_url'],'/');
        if($document_type=='page'){
            $original_permalink_absolute = $original_blog_home . '/?page_id=' . $original_id;
        }else{
            $original_permalink_absolute = $original_blog_home . '/?p' . $original_id;
        }
        
        $parts = parse_url(get_option('home'));
        $blog_path = trim($parts['path'],'/');
               
        delete_post_meta($post_id,'_iclt_all_urls_translated');
               
        //fix the links in this post according to the links map 
        $this_post_content = $wpdb->get_var("SELECT post_content FROM {$wpdb->posts} WHERE ID={$post_id}");                
        $int = preg_match_all('|<a([^>]*)href="'.$original_blog_home.'([^"]*)"([^>]*)>|i',$this_post_content,$matches);
        if($int){            
            $not_found = 0;
            foreach($matches[2] as $m){                                
                $orig_url =  $original_blog_home . $m;
                if(preg_match('#/\?page_id=([0-9]+)#i',$m,$mtch)){                    
                    $orig_id=$mtch[1];                    
                    $found_in_map_id = $wpdb->get_var("SELECT translated_id FROM {$wpdb->prefix}iclt_urls_map 
                        WHERE post_id='{$orig_id}' AND language='{$translated_language}'");
                    if(!$found_in_map_id){
                        $not_found++;
                        continue;
                    }
                    $wpdb->get_var("SELECT translated_id FROM {$wpdb->prefix}iclt_urls_map WHERE post_id='{$orig_id}'");
                    $trans_url = get_permalink($found_in_map_id);                       
                }else{                    
                    $found_in_map_id = $wpdb->get_var("SELECT translated_id FROM {$wpdb->prefix}iclt_urls_map 
                        WHERE permalink='{$orig_url}' AND language='{$translated_language}'");
                    if(!$found_in_map_id){
                        $not_found++;
                        continue;
                    }                    
                    $trans_url = get_permalink($found_in_map_id);   
                }
                $orig_url = str_replace('?','\?',$orig_url);
                $this_post_content = preg_replace('@<a([^>]*)href="'.$orig_url.'"([^>]*)>@im','<a$1href="'.$trans_url.'"$2>',$this_post_content);                  
                $content_updated = true;
            }            
            if(isset($content_updated)){
                $wpdb->update($wpdb->posts, array('post_content'=>$this_post_content), array('ID'=>$post_id));            
            }
        }
        // also mark the post as clean if there is not link going back to the original blog.            
        if(!$int || $not_found==0){
            update_post_meta($post_id,'_iclt_all_urls_translated',1);
        }
        
        $fargs[0] = $user_login;
        $fargs[1] = $user_pass;
        $fargs[2] = $document_type;
        $fargs[3] = $original_id;
        $fargs[4] = $original_permalink;
        $fargs[5] = $translated_id;
        $fargs[6] = $translated_language;
        $fargs[7] = $original_blog_home;
        
        ICanLocalizeAddUrlTranslation($fargs);
        
        /*
        // add/update details in the urls map table
        $is_update = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}iclt_urls_map WHERE post_id={$original_id} AND translated_id='{$translated_id}'");
        if($is_update){
            $wpdb->update($wpdb->prefix.'iclt_urls_map',
                array(
                    'document_type'=>$document_type,
                    'post_id'=>$original_id,
                    'permalink'=>$original_permalink,
                    'translated_id'=>$translated_id,
                    'language'=>$translated_language
                ),
                array(
                    'id'=>$is_update
                )
            );      
        }else{            
            $wpdb->insert($wpdb->prefix.'iclt_urls_map',array(
                'document_type'=>$document_type,
                'post_id'=>$original_id,
                'permalink'=>$original_permalink,
                'translated_id'=>$translated_id,
                'language'=>$translated_language      
            ));
        }            
        
        // check for posts using this link from the original blog and language
        // look for WP (pretty) urls)
        $mposts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} 
            WHERE post_content LIKE '%{$original_permalink}%' AND post_type IN ('post','page')");                
        foreach($mposts as $p){
            $found_post_language = get_post_meta($p->ID, '_ican_language', true);            
            if($found_post_language!=$translated_language){
                continue;
            }                    
            //$content_updated = str_replace($original_permalink, $permalink, $p->post_content);
            $content_updated = preg_replace('|<a([^>]*)href="'.$original_permalink.'"([^>]*)>|i',
                '<a$1href="'.$permalink.'"$2>', $this_post_content);
            $wpdb->update($wpdb->posts, array('post_content'=>$content_updated), array('ID'=>$p->ID));            
        }

        // check for posts using this link from the original blog and language
        // look for absolute urls
        $mposts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} 
            WHERE post_content LIKE '%{$original_permalink_absolute}%' AND post_type IN ('post','page')");                
        foreach($mposts as $p){
            $found_post_language = get_post_meta($p->ID, '_ican_language', true);            
            if($found_post_language!=$translated_language){
                continue;
            }                                             
            //$content_updated = str_replace($original_permalink_absolute, $permalink, $p->post_content);
            $content_updated = preg_replace('|<a([^>]*)href="'.$original_permalink_absolute.'"([^>]*)>|i',
                '<a$1href="'.$permalink.'"$2>', $this_post_content);
            $wpdb->update($wpdb->posts, array('post_content'=>$content_updated), array('ID'=>$p->ID));            
        }
        */
        
        return intval($post_id);
        
} 

function ICanLocalizeAddUrlTranslation($args){
    global $wpdb;
    
    $user_login  = $args[0];
    $user_pass   = $args[1];
    
    $document_type = $args[2];
    $original_id = $args[3];
    $original_permalink = $args[4];    
    $translated_id = $args[5];
    $translated_language = $args[6];
    $original_blog_home = $args[7];
    
    if ( !get_option( 'enable_xmlrpc' ) ) {
        return sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php'));
    }
    if (!user_pass_ok($user_login, $user_pass)) {
        return 'Bad user/pass combination';
    }
    
    if($document_type=='page'){
        $original_permalink_absolute = $original_blog_home . '/?page_id=' . $original_id;
    }else{
        $original_permalink_absolute = $original_blog_home . '/?p' . $original_id;
    }
    
    // add/update details in the urls map table
    $is_update = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}iclt_urls_map WHERE post_id={$original_id} AND translated_id='{$translated_id}'");
    if($is_update){
        $wpdb->update($wpdb->prefix.'iclt_urls_map',
            array(
                'document_type'=>$document_type,
                'post_id'=>$original_id,
                'permalink'=>$original_permalink,
                'translated_id'=>$translated_id,
                'language'=>$translated_language
            ),
            array(
                'id'=>$is_update
            )
        );      
    }else{            
        $wpdb->insert($wpdb->prefix.'iclt_urls_map',array(
            'document_type'=>$document_type,
            'post_id'=>$original_id,
            'permalink'=>$original_permalink,
            'translated_id'=>$translated_id,
            'language'=>$translated_language      
        ));
    }            
    
    // check for posts using this link from the original blog and language
    // look for WP (pretty) urls)
    $mposts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} 
        WHERE post_content LIKE '%{$original_permalink}%' AND post_type IN ('post','page')");                
    foreach($mposts as $p){
        $found_post_language = get_post_meta($p->ID, '_ican_language', true);            
        if($found_post_language!=$translated_language){
            continue;
        }                    
        $original_permalink = str_replace('?','\?',$original_permalink);
        $content_updated = preg_replace('|<a([^>]*)href="'.$original_permalink.'"([^>]*)>|i',
            '<a$1href="' . get_permalink($translated_id) . '"$2>', $p->post_content);
        $wpdb->update($wpdb->posts, array('post_content'=>$content_updated), array('ID'=>$p->ID));            
    }

    // check for posts using this link from the original blog and language
    // look for absolute urls
    $mposts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} 
        WHERE post_content LIKE '%{$original_permalink_absolute}%' AND post_type IN ('post','page')");                
    foreach($mposts as $p){
        $found_post_language = get_post_meta($p->ID, '_ican_language', true);            
        if($found_post_language!=$translated_language){
            continue;
        }                                             
        $original_permalink_absolute = str_replace('?','\?',$original_permalink_absolute);
        $content_updated = preg_replace('|<a([^>]*)href="'.$original_permalink_absolute.'"([^>]*)>|i',
            '<a$1href="' . get_permalink($translated_id) . '"$2>', $p->post_content);
        $wpdb->update($wpdb->posts, array('post_content'=>$content_updated), array('ID'=>$p->ID));            
    }    
}

function ICanLocalizeTBGetCategories($args){
        global $wpdb;
        $user_login  = $args[0];
        $user_pass   = $args[1];
        
        if ( !get_option( 'enable_xmlrpc' ) ) {
            return array('err_code'=>3, 'err_str'=>sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php')));
        }
        
        if (!user_pass_ok($user_login, $user_pass)) {
            return array('err_code'=>2, 'err_str'=>__('user or password don\'t match'));
        }        
        
        $categories_struct = array();
  
        if ( $cats = get_categories('get=all') ) {
            foreach ( $cats as $cat ) {
                $struct['id'] = $cat->term_id;
                $struct['parent_id'] = $cat->parent;
                $struct['description'] = $cat->description;
                $struct['name'] = $cat->name;
                $struct['slug'] = $cat->slug;
                
                $categories_struct[] = $struct;
            }
        }
        return $categories_struct;
}
?>