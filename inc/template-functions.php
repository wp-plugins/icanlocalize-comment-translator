<?php
if(!function_exists('show_translation_links')){
    
    // the template function
    function show_translation_links($echo = true, $wording="This #posttype# is also available in %s", $sep1=', ', $sep2=' and '){
        global $id;
        if(!$id || (!is_page() && !is_single())) return;
        
        $post_language = get_post_meta($id,'_ican_language',true);
        $link_info_for_language = get_post_meta($id,'_ican_link_info_for_language',true);
        
        if(!$post_language || !is_array($link_info_for_language)) return;
        
        foreach($link_info_for_language as $lang=>$data){
            if($lang==$post_language) continue;
            if(!$data['title']){
                $data['title'] = '';
            }
            $links[] = '<a href="'.$data['permlink'].'" title="'.attribute_escape($data['title']).'">'.$data['display_language'].'</a>';        
        }
        if(!is_array($links)) return;
        $struct = is_page()?'page':'post';
            
        if(count($links) > 1){
            $last_item = array_pop($links);
        }
        $langs = join($sep1, $links);
        if(isset($last_item)){
            $langs .= $sep2 . $last_item;    
        }
                
        $wording = str_replace('#posttype#',$struct,$wording);
        $s = sprintf(__($wording) , $langs);
        if($echo){
            echo $s; 
            return true;
        }else{
            return $s;
        }
    }  
    
}
?>