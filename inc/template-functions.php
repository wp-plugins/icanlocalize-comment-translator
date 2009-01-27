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

if(!function_exists('iclt_language_selector')){ 
    function iclt_language_selector($lang_array){
        global $wp_query;            
        $id = $wp_query->post->ID;
        if($id && (is_page() || is_single())){
            $post_language = get_post_meta($id,'_ican_language',true);
            $link_info_for_language = get_post_meta($id,'_ican_link_info_for_language',true);        
        }
    
        $iclt_settings = get_option('iclt_settings');
        $blog_language = $iclt_settings['iclt_blog_language'];
        //$blog_lan
    
        $k = 0;
        $sel_langs = array();
        foreach($lang_array as $language_name_en=>$default_url){
            $k++;
            if(isset($link_info_for_language[$language_name_en])){
                $sel_langs[$k] = array('name'=>$link_info_for_language[$language_name_en]['display_language'], 'url'=>$link_info_for_language[$language_name_en]['permlink']);
                if($link_info_for_language[$language_name_en]['english_name'] == $post_language){
                    $cur_lang = $k;            
                }
            }else{
                $sel_langs[$k] = array('name'=>$language_name_en, 'url'=>$default_url);
                if($language_name_en == $post_language || $language_name_en == $blog_language){
                    $cur_lang = $k;            
                }        
            }
        }
        ?>
        <div id="lang_sel">
            <ul>
                <li><a href="#"><?php echo $sel_langs[$cur_lang]['name']?><!--[if IE 7]><!--></a><!--<![endif]-->
                    <!--[if lte IE 6]><table><tr><td><![endif]-->
                    <ul>
                        <?php foreach($sel_langs as $k=>$default_url): if($k==$cur_lang) continue; ?>
                        <li><a href="<?php echo $sel_langs[$k]['url']?>"><?php echo $sel_langs[$k]['name']?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <!--[if lte IE 6]></td></tr></table></a><![endif]-->
                </li>
            </ul>    
        </div>
        <?php    
    }
    
    function iclt_lang_sel_nav_css($show = true){
        //make it MU and WP compatible   
        $plugins_folder = basename(dirname(dirname(dirname(__FILE__))));
        $link_tag = '<link rel="stylesheet" href="'. get_option('home') . '/wp-content/' . $plugins_folder . '/'. 
            basename(dirname(dirname(__FILE__))) . '/css/language_selector.css?ver=1" type="text/css" media="all" />';
        if(!$show){
            return $link_tag;
        }else{
            echo $link_tag;
        }
    }
    
    add_action('init','iclt_lang_sel_nav_ob_start');
    function iclt_lang_sel_nav_ob_start(){
        ob_start('iclt_lang_sel_nav_prepend_css');
    }
    add_action('wp_head','iclt_lang_sel_nav_ob_end');
    function iclt_lang_sel_nav_ob_end(){
        ob_end_flush();             
    }
    function iclt_lang_sel_nav_prepend_css($buf){
        return preg_replace('#</title>#i','</title>' . PHP_EOL . PHP_EOL . iclt_lang_sel_nav_css(false), $buf);
    }    
}
?>