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
        
        $fh = fopen(dirname(__FILE__) . '/languages.csv', 'r');
        $idx = 0;
        while($data = fgetcsv($fh)){
            if($idx == 0){
                foreach($data as $k=>$v){
                    if($k < 3) continue;
                    $lang_idxs[] = $v; 
                }
                
            }else{
                foreach($data as $k=>$v){
                    if($k < 3) continue;
                    $langs_names[$lang_idxs[$idx-1]][$lang_idxs[$k-3]] = $v; 
                }
            }
            $idx++;
        }
        
        $id = $wp_query->post->ID;
        if($id && (is_page() || is_single())){
            $post_language = get_post_meta($id,'_ican_language',true);
            $link_info_for_language = get_post_meta($id,'_ican_link_info_for_language',true);        
        }
    
        // try to determine whether the main translation plugin is installed and blog language defined
        $tp_installed = false;
        foreach($GLOBALS['current_plugins'] as $cp){
            if(preg_match('#/icanlocalize_translator\.php$#',$cp)){
                $tp_installed = true;
                break;
            }
        }
        if($tp_installed){        
            $iclt_settings = get_option('iclt_settings');
            $blog_language = $iclt_settings['iclt_blog_language'];
        }else{
            $blog_language = get_option('iclt_tb_blog_language');
        }
        
        $k = 0;
        $sel_langs = array();
        foreach($lang_array as $language_name_en=>$default_url){
            $k++;
            if(isset($link_info_for_language[$language_name_en])){
                $sel_langs[$k] = array(
                    'current_name'=> $langs_names[$language_name_en][$post_language], 
                    'url'=>$link_info_for_language[$language_name_en]['permlink'],
                    'native_name'=> $langs_names[$link_info_for_language[$language_name_en]['english_name']][$link_info_for_language[$language_name_en]['english_name']]
                );
                if($link_info_for_language[$language_name_en]['english_name'] == $post_language){
                    $cur_lang = $k;            
                }
            }else{
                $sel_langs[$k] = array(
                    'current_name'=>$langs_names[$language_name_en][$blog_language],
                    'url'=>$default_url,
                    'native_name'=> $langs_names[$language_name_en][$language_name_en]
                );
                if($language_name_en == $post_language || $language_name_en == $blog_language){
                    $cur_lang = $k;            
                }        
            }
        }        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if(preg_match('#MSIE ([0-9]+)\.[0-9]#',$user_agent,$matches)){
            $ie_ver = $matches[1];
        }
        
        ?>
        <div id="lang_sel">
            <ul>
                <li><a href="#" class="lang_sel_sel"><?php echo $sel_langs[$cur_lang]['native_name']?><?php if(!isset($ie_ver) || $ie_ver > 6): ?></a><?php endif; ?>
                    <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>
                    <ul>
                        <?php foreach($sel_langs as $k=>$default_url): if($k==$cur_lang) continue; ?>
                        <li><a href="<?php echo $sel_langs[$k]['url']?>"><?php echo $sel_langs[$k]['native_name']?> (<?php echo $sel_langs[$k]['current_name'] ?>)</a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?> 
                </li>
            </ul>    
        </div>
        <?php    
    }
    
    function iclt_lang_sel_nav_css($show = true){
        //make it MU and WP compatible   
        $plugins_folder = basename(dirname(dirname(dirname(__FILE__))));
        $link_tag = '<link rel="stylesheet" href="'. get_option('home') . '/wp-content/' . $plugins_folder . '/'. 
            basename(dirname(dirname(__FILE__))) . '/css/language_selector.css?ver=2" type="text/css" media="all" />';
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

function language_selector_widget_control(){
    global $icltc, $wpdb; 
    $all_checked = false;
    
    
    if ( isset($_POST['language-selector-submit']) ) {
        foreach($_POST['language_selector_options'] as $p){
            $exp = explode('@SEP@',$p);
            $language_selector_options[$exp[0]] = $exp[1];
        }        
        update_option('iclt_language_selector_options', $language_selector_options);
    }else{
        $language_selector_options = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='iclt_language_selector_options'");
        if(is_null($language_selector_options) || -1 == $language_selector_options){
            add_option('iclt_language_selector_options',-1,0,1);
            $all_checked = true;
        }else{
            $language_selector_options = maybe_unserialize($language_selector_options);
        }
    }    
    
    require_once(ABSPATH . '/wp-includes/class-snoopy.php');
    $website_info = $icltc->get_website_info(); 
    
    $source_url = $website_info['info']['website']['attr']['url'];
    
    $languages = $website_info['info']['website']['translation_languages'];
    if(isset($languages['translation_language'][0])){
        $langs = $languages['translation_language'];
    }else{
        $langs[0] = $languages['translation_language'];
    }
    foreach($langs as $l){
        $blog_langs[$l['attr']['from_language_name']] = $source_url;
        $blog_langs[$l['attr']['to_language_name']] = $l['attr']['url'];
    }

    foreach($blog_langs as $name=>$url){
        if($all_checked || in_array($name, array_keys((array)$language_selector_options))){
            $checked = 'checked="ckeched"';
        }else{
            $checked = '';
        }        
        ?><label><input type="checkbox" name="language_selector_options[]" value="<?php echo $name . '@SEP@' . $url ?>" <?php echo $checked ?> />&nbsp;<?php
        echo $name . ' [' . $url . ']</label><br />';
    }
    ?>
    <input id="language-selector-submit" type="hidden" value="1" name="language-selector-submit"/>
    <?php
}

function language_selector_widget_init(){
    function language_selector_widget(){
        echo $before_widget;
        echo $before_title;
        $language_selector_options = get_option('iclt_language_selector_options');
        if($language_selector_options){
            iclt_language_selector($language_selector_options);        
        }
    }
    wp_register_sidebar_widget('languages_selector', __('Language Selector'), 'language_selector_widget');
    wp_register_widget_control('languages_selector', __('Language Selector'), 'language_selector_widget_control' ); 
}
add_action('plugins_loaded', 'language_selector_widget_init');

?>