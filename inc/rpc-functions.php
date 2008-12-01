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
        
        if ( !get_option( 'enable_xmlrpc' ) ) {
            return sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php'));
        }
        if (!user_pass_ok($user_login, $user_pass)) {
            return 'Bad user/pass combination';
        }
                
        if($post_id){            
            update_post_meta($post_id,'_ican_link_info_for_language',$link_info_for_language);
        }
        return intval($post_id);
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