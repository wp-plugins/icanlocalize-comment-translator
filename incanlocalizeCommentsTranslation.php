<?php
/*
Plugin Name: ICanLocalize Comment Translator
Plugin URI: http://sitepress.org/wordpress-translation/
Description: Receives professionally translated posts and pages from ICanLocalize's translation system and allows comment moderation in your language. To configure the plugin, you'll need an setup an account at <a href="http://www.icanlocalize.com">www.icanlocalize.com</a>. <a href="options-general.php?page=iclttc">Configure &raquo;</a>.
Author: ICanLocalize
Author URI: http://www.icanlocalize.com
Version: 1.3.1
*/

/*
    This file is part of ICanLocalize Comment Translator.

    ICanLocalize Comment Translator is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    ICanLocalize Comment Translator is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with ICanLocalize Comment Translator.  If not, see <http://www.gnu.org/licenses/>.
*/

require dirname(__FILE__) . '/inc/google_languages_map.inc';
require dirname(__FILE__) . '/inc/config.inc';
require dirname(__FILE__) . '/inc/rpc-functions.php';
require dirname(__FILE__) . '/lib/json_functions.php';
require dirname(__FILE__) . '/lib/xml2array.php';
require dirname(__FILE__) . '/version.php';
require dirname(__FILE__) . '/upgrade_schema.php';
require dirname(__FILE__) . '/inc/template-functions.php';

define('API_TRANSLATE_URL',"http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=%s&langpair=%s|%s");
$ican_google_translation_request_fail_flag = 0;

define('ICLT_CT_PLUGIN_URL', get_option('siteurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)));

class ICanLocalizeTBTranslate{
    private $post_comments_translated = array();
    private $enable_comments_translation;
    private $enable_replies_translation;
    private $site_id;
    private $access_key;
    private $valid;
    private $pending_requests = array();
    
    function __construct(){  
        $settings = get_option('iclt_tb_settings');
        $this->site_id = $settings['site_id'];
        $this->access_key = $settings['access_key'];
        $this->valid = $settings['valid'];
        
        add_action('init', array($this,'init'));            
        
        if($_POST['Submit']){            
            add_action('init',array($this,'save_settings'));           
        }
        
        if($_POST['translate_reply'] && $this->valid){
            add_action('comment_post', array($this,'pre_submit_comment_to_translation'));
        }
        
        add_action('delete_comment', array($this,'delete_comment_actions'));
        
        add_action('show_user_profile', array($this,'show_user_options'));
        add_action('personal_options_update', array($this,'save_user_options'));                 
        
        add_action('comment_form', array($this,'comment_form_options'));
        
        add_filter('xmlrpc_methods',array($this, 'add_custom_xmlrpc_methods'));
        
        add_action('admin_menu',array($this,'management_page'));
        add_action('admin_notices', array($this,'admin_notices'));
        
        add_action('manage_comments_nav', array($this,'get_post_translated_comments'));
    }
    
    function init(){
        global $userdata, $wpdb;

        if(!$this->valid && !$_POST['Submit'] && $_POST['_wpnonce']!= wp_create_nonce('update-incalocalize-comments')){
            $_GET['iclt_error']=__('Invalid settings.');        
        }        
        
        get_currentuserinfo();
        
        $this->enable_comments_translation = get_usermeta($userdata->ID,'enable_comments_translation',true);        
        $this->enable_replies_translation = get_usermeta($userdata->ID,'enable_replies_translation',true);                        
        
        if($this->enable_comments_translation){
            add_filter('page_template', array($this,'get_post_translated_comments'));            
            add_filter('single_template', array($this,'get_post_translated_comments'));            
            add_filter('get_comment_text', array($this,'get_comment_text_translated'));        
            add_filter('comment_edit_pre', array($this,'get_comment_text_translated'));        
            add_filter('comment_excerpt', array($this,'get_comment_text_translated_excerpt'));        
            
            add_filter('comments_array', array($this,'show_pending_request_status'));
            add_filter('comment_excerpt', array($this,'show_pending_request_status_excerpt'));        
            
            add_action('admin_head',array($this,'js_scripts'));  
            add_action('wp_head',array($this,'js_scripts'));  
        }
        
        $this->pending_requests = $wpdb->get_col("SELECT comment_id FROM {$wpdb->prefix}comments_translation_requests");
        
        if($_POST['iclttc_ajx_action']){            
            $this->ajax_actions($_POST['iclttc_ajx_action']);
            exit;
        }        
        
    }
    
    function management_page(){
        add_options_page('ICanLocalize Comment Translator','ICanLocalize Comment Translator','10', 'iclttc' , array($this,'management_page_content'));
    }
    
    function management_page_content(){
        global $wpdb;
        $links_map_size = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}iclt_urls_map");        
        if($_GET['iclt_error']){
            $help_icon_image = 'RO-Mx1-24_circle-red-i.png';
            $help_bg_style = ';background-color:#ffd3d3;';
        }else{
            $help_icon_image = 'RO-Mx1-24_circle-help-1.png';
            $help_bg_style = '';            
        }   
        // try to determine whether the main translation plugin is installed and blog language defined
        if(!defined('ICLT_CURRENT_VERSION')){
            $blog_lang = get_option('iclt_tb_blog_language');
            $res = $wpdb->get_results("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_ican_language'");
            $default_lang = $r[0]->meta_value; 
            foreach($res as $r){                
                $post_id = $wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_ican_language' AND meta_value='".$wpdb->escape($r->meta_value)."' LIMIT 1");
                $has_from = $wpdb->get_var("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id={$post_id} AND meta_key='_ican_from_language'");
                if(!$has_from){
                    $default_lang = $r->meta_value; 
                }
                $blog_langs[] = $r->meta_value;
            }
            if(!$blog_lang){
                update_option('iclt_tb_blog_language', $default_lang);
                $blog_lang = $default_lang;
            }
        }    
        include dirname(__FILE__).'/options_interface.php';
    }
    
    function save_settings(){
        $nonce = wp_create_nonce('update-incalocalize-comments');
        if($nonce != $_POST['_wpnonce']) return;
        
        $this->site_id = intval($_POST['iclt_site_id'])>0?intval($_POST['iclt_site_id']):'';
        $this->access_key = $_POST['iclt_access_key'];
        
        $this->validate_settings();  
        if(!$this->valid){                                                                          
            $_GET['iclt_error'] .= __('Invalid settings.');        
        }else{
            $_GET['updated']=true;        
        }
        $iclt_settings['site_id'] = $this->site_id;
        $iclt_settings['access_key'] = mysql_real_escape_string($this->access_key);
        $iclt_settings['valid'] = $this->valid;
        update_option('iclt_tb_settings',$iclt_settings);                
        
        if($_POST['blog_language']){
            update_option('iclt_tb_blog_language', $_POST['blog_language']);
        }
        
        //update user settings
        global $userdata, $wpdb;
        $level_key = $wpdb->prefix . 'user_level';
        $query = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$level_key'";
        $users = $wpdb->get_col( $query );
        foreach($users as $uid){
            update_usermeta($uid,'enable_comments_translation',intval($_POST['enable_comments_translation'][$uid]));
            update_usermeta($uid,'enable_replies_translation',intval($_POST['enable_replies_translation'][$uid]));
        }
    }
    
    function admin_notices(){
        /*
        if($_GET['updated']){
        ?><div id="message" class="updated fade"><p><?php echo __('Settings updated') ?></p></div><?php            
        }
        */
        /*
        if($_GET['iclt_error']){
            ?><div id="message" class="error"><p><?php echo $_GET['iclt_error'] ?></p></div><?php            
        }
        */
    }
    
    function pre_submit_comment_to_translation($comment_id){
        global $wpdb, $google_languages_map;
        $c = get_comment($comment_id);
        $comment_content = $c->comment_content;
        $comment_id = $c->comment_ID;
        
        $original_language = get_post_meta($c->comment_post_ID,'_ican_from_language',true);
        $current_language = get_post_meta($c->comment_post_ID,'_ican_language',true);
                
        $wpdb->query("INSERT INTO {$wpdb->prefix}comments_translated(id,translation) VALUES({$comment_id},'{$comment_content}')");        
        $request_id = $this->send_comment_to_translation($comment_content, $original_language, $current_language);        
        if($request_id){                
            $wpdb->query("INSERT INTO {$wpdb->prefix}comments_translation_requests(comment_id,request_id, date_request) 
                VALUES({$comment_id},{$request_id},'".date('Y-m-d H:i:s')."')");
            $wpdb->query("UPDATE {$wpdb->comments} SET comment_approved='0' WHERE comment_id={$comment_id}");
        }else{
            //log error here    
            //die("Something went wrong " . $request_id);
        }
    }  
      
    function translate_comment_text($comment_id){
        global $wpdb, $google_languages_map;
        $c = $wpdb->get_row("SELECT comment_content, comment_post_ID FROM {$wpdb->comments} WHERE comment_ID={$comment_id}");
        $comment_content = $c->comment_content;
                                                 
        $original_language = get_post_meta($c->comment_post_ID,'_ican_from_language',true);
        $current_language = get_post_meta($c->comment_post_ID,'_ican_language',true);
        
        if(!$original_language || !$current_language){
            return $comment_content;
        }           

        $original_language = $google_languages_map[strtoupper($original_language)];
        $current_language = $google_languages_map[strtoupper($current_language)]; 
        
        
        $comment_content_translated = $this->translate($current_language, $original_language, $comment_content);            
        
        if($comment_content_translated){            
            $this->add_translated_comment($comment_id, $comment_content_translated);
        }else{
            $comment_content_translated = $comment_content; 
        }
        
        return $comment_content_translated; 
    }
    
    function add_translated_comment($id, $content){
        global $wpdb;
        $id = intval($id);
        $content = mysql_real_escape_string($content);
        $wpdb->query("INSERT INTO {$wpdb->prefix}comments_translated(id,translation) VALUES('{$id}','{$content}')");                
    }
    
    function get_comment_text_translated($comment_text, $show_tooltip = true){        
        global $comment, $wpdb;
        $id = $comment->comment_ID;   
        $language = get_post_meta($comment->comment_post_ID,'_ican_language',true);
        if($this->post_comments_translated[$id]){
            $translation = $this->post_comments_translated[$id];
        }else{
            $translation = $wpdb->get_row("SELECT id, translation FROM {$wpdb->prefix}comments_translated WHERE id='{$id}'");                        
            if(is_null($translation->id)){
                $translation = $this->translate_comment_text($id);             
            }else{
                $translation = $translation->translation;
            }
        }
        if($show_tooltip && $language && !in_array($id,$this->pending_requests)){
            $translation .= '<br /><small><a class="iclt_popup_trig" href="#" title="'.htmlspecialchars($comment_text).'">'.  
            sprintf(__('Comment in %s'),$language).'</a></small>';
        }
        return $translation;
    }
    
    function get_comment_text_translated_excerpt($comment_text, $show_tooltip = true){
        global $comment;
        $id = $comment->comment_ID;           
        $language = get_post_meta($comment->comment_post_ID,'_ican_language',true);
        $excerpt = $this->get_comment_text_translated($comment_text, false);
        $excerpt = strip_tags($excerpt); 
        $blah = explode(' ', $excerpt);
        if (count($blah) > 20) {
            $k = 20;
            $use_dotdotdot = 1;
        } else {
            $k = count($blah);
            $use_dotdotdot = 0;
        }
        $excerpt = '';
        for ($i=0; $i<$k; $i++) {
            $excerpt .= $blah[$i] . ' ';
        }
        $excerpt .= ($use_dotdotdot) ? '...' : '';         
        
        if($show_tooltip && $language && !in_array($id,$this->pending_requests)){
            $excerpt .= '<br /><small><a class="iclt_popup_trig" href="#" title="'.htmlspecialchars($comment_text).'">'.  
            sprintf(__('Comment in %s'), $language).'</a></small>';
        }
        
        return $excerpt;
    }
    
    function &get_post_translated_comments($arg=null){
        global $wp_query, $wpdb, $user_ID;
        global $comments;        
        if(!is_single() && !is_page() && !is_admin()) return;
        $post_id = $wp_query->post->ID;        
        if($post_id){
            $cond = "p1.comment_post_ID='{$post_id}' AND comment_approved = 1 OR comment_approved = 0";
        }else{
            if($comments){
                foreach($comments as $c){ $cs[] = $c->comment_ID; }
                $cond = "p1.comment_ID IN (" . $cids = join(',',$cs) . ")";            
            }
        }
        $translated_comments = $wpdb->get_results("
            SELECT p2.id, p2.translation, p1.comment_approved, p1.user_id FROM {$wpdb->comments} p1 
            LEFT JOIN {$wpdb->prefix}comments_translated p2 ON p1.comment_ID=p2.id
            WHERE $cond 
        ");                  
        if($translated_comments){
            foreach($translated_comments as $t){                
                if($t->comment_approved=='0' && $t->user_id==$user_ID){
                    $t->translation = '<small style="color:#f77">' . 
                        __("You submitted this comment to translation.\nThe comment will visible to others as soon as the translation is completed.") 
                        . '</small><br />' . $t->translation;
                }
                $this->post_comments_translated[$t->id] = $t->translation;
            }   
        }
        return $arg;
    }
    
    public function translate($from_language, $to_language, $text){
        global $ican_google_translation_request_fail_flag;
        if($ican_google_translation_request_fail_flag) return '';
        
        $url = sprintf(API_TRANSLATE_URL, urlencode($text), $from_language, $to_language);                
        $url = str_replace('|','%7C',$url);
        
        if(!function_exists('curl_init')){
            require_once(ABSPATH . '/wp-includes/class-snoopy.php');
            $c = new Snoopy();
            $c->_fp_timeout = 3;
            $c->referer = get_option('home').'/';
            $er = ini_get('display_errors');
            ini_set('display_errors','off');
            $c->fetch($url);
            ini_set('display_errors',$er);
            $body = $c->results;
            if($c->status='200'){
                $translation = json_decode($body);        
            }else{
                $ican_google_translation_request_fail_flag = 1;
            }
        }else{        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_REFERER, get_option('home').'/');
            $body = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);     
            if($info['http_code']=='200'){
                $translation = json_decode($body);        
            }
        }                
        if($translation->responseData->translatedText){
            return $translation->responseData->translatedText;
        }else{
            return '';
        }
    }
    
    function show_user_options(){
        $enable_comments_translation = $this->enable_comments_translation;
        $enable_replies_translation = $this->enable_replies_translation;
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php echo __('Comments Translation:') ?></th>
                    <td>
                        <input type="checkbox" name="enable_comments_translation" id="enable_comments_translation" value="1" 
                        <?php if($enable_comments_translation): ?> checked="checked" <?php endif?> /> 
                        <?php echo __('Show translated comments.') ?></label>                         
                        <span style="color:#888"><?php echo __('This enables you to see the comments translated in the language that the post was originally written in. The translation is automatic (made by a machine) so it might not be 100% accurate. It\'s also free.')?></span>
                        <br />
                        <input type="checkbox" name="enable_replies_translation" id="enable_replies_translation" value="1" 
                        <?php if($enable_replies_translation): ?> checked="checked" <?php endif?> /> 
                        <?php echo __('Translate my replies.') ?></label>            
                        <span style="color:#888"><?php echo __('When this is checked you can write comments in the post\'s original language. They will not be published immediately but sent to the ICanLocalize translation server and translated. Once translated they are published automatically on your blog.')?></span>             
    
                    </td>
                </tr>
            </tbody>
        </table>        
        <?php
    }
    
    function save_user_options(){
        $user_id = $_POST['user_id'];
        if($user_id){
            update_usermeta($user_id,'enable_comments_translation',$_POST['enable_comments_translation']);        
            update_usermeta($user_id,'enable_replies_translation',$_POST['enable_replies_translation']);        
        }
    }
   
    function delete_comment_actions($comment_id){
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}comments_translated WHERE id=".$comment_id);
        $wpdb->query("DELETE FROM {$wpdb->prefix}comments_translation_requests WHERE comment_id=".$comment_id);
    }
    
    function comment_form_options(){        
        global $post, $userdata;
        if($userdata->user_level < 7 ) return;
        
        $original_language = get_post_meta($post->ID,'_ican_from_language',true);
        $current_language = get_post_meta($post->ID,'_ican_language',true);
        
        if(!$original_language || !$current_language) return;                
        ?> 
        <label style="cursor:pointer">       
        <input style="width:15px;" type="checkbox" name="translate_reply" <?php if($this->enable_replies_translation):?>checked="checked"<?php endif;?> 
        <?php if(!$this->valid): ?>disabled="disabled"<?php endif?>/>
        <span><?php echo sprintf(__('Translate from %s into %s'),$original_language, $current_language); ?></span>
        </label>
        <?php if(!$this->valid): ?><span style="color:#f00">(<?php echo __('Disabled - invalid settings') ?>)</span><?php endif?>
        <?php
    }
    
    function show_pending_request_status($comments_array){
        global $user_ID;
        foreach($comments_array as $k=>$c){              
            if($c->comment_approved==0 && $c->user_id==$user_ID){
                $comments_array[$k]->comment_approved=1;        
            }
        }        
        return $comments_array;
    }
    
    function show_pending_request_status_excerpt($comment_text){
        global $comment, $user_ID;
        if($comment->comment_approved=='0' && $comment->user_id==$user_ID){            
            $comment_text = '<small style="color:#f77">' . 
                __("You submitted this comment to translation.\nThe comment will visible to others as soon as the translation is completed.") 
                . '</small><br />' . $comment_text;
        }
        
        return $comment_text;
    }
    
    
    function add_custom_xmlrpc_methods($methods){
        $methods['ictl.icanUpdateTextTranslation'] = 'ICanLocalizeUpdateTextTranslation';
        $methods['ictl.icanTBValidate'] = 'ICanLocalizeTBValidate';        
        $methods['ictl.setLanguagesInfoDest'] = 'ICanLocalizeSetLanguagesInfoDest';        
        if(!$methods['ictl.processPostAfterSubmission']){
            $methods['ictl.processPostAfterSubmission'] = 'ICanLocalizeTBProcessPostAfterSubmission';
        }
        $methods['ictl.getCategoriesDest'] = 'ICanLocalizeTBGetCategories';
        $methods['ictl.addUrlTranslation'] = 'ICanLocalizeAddUrlTranslation';        
        return $methods;
    }
    
    function validate_settings(){
        require_once(ABSPATH . '/wp-includes/class-snoopy.php');
        $request =  ICL_API_ENDOINT . 'websites/' . intval($this->site_id) . '.xml?accesskey=' . $this->access_key;
        
        $de = ini_get('display_errors');
        ini_set('display_errors','off');
        $c = new Snoopy();
        $c->timed_out = 5;
        $url_parts = parse_url($request);
        $https = $url_parts['scheme']=='https';
        $c->fetch($request);            
        if((!$c->results || $c->timed_out) && $https){
            $c->fetch(str_replace('https://','http://',$request));  
        }

        if($c->error){
            $_GET['iclt_error'] = __('Error: Connection to ICanLocalize is temporary not available. It should be 
back in a few minutes') . '<br /><br />';
            $this->valid = false;
            return false;
        }        
        
        ini_set('display_errors',$de);  
        $res = xml2array($c->results);
        $this->valid = $res['info']['status']['attr']['err_code']==0;
    }
    
    function send_comment_to_translation($text,$from_language,$to_language){
        require_once(ABSPATH . '/wp-includes/class-snoopy.php');
        $request_url = ICL_API_ENDOINT . 'websites/'.$this->site_id.'/create_message.xml';
      
        $parameters['accesskey'] = $this->access_key;
        $parameters['body'] = $text;          
        $parameters['from_language'] = $from_language;          
        $parameters['to_language'] = $to_language;          
      
        $c = new Snoopy();
        $c->_fp_timeout = 10;
        $url_parts = parse_url($request_url);
        $https = $url_parts['scheme']=='https';
        $c->submit($request_url, $parameters);            
        if((!$c->results || $c->timed_out) && $https){
            $c->submit(str_replace('https://','http://',$request_url), $parameters);  
        }  
        $results = xml2array($c->results,1);                            
        
        if($results['info']['status']['attr']['err_code']!=0){
            die("Error:".$results['info']['status']['attr']['err_code']);
        }else{
            return $results['info']['result']['attr']['id'];
        }                      
    }        
    
    function get_website_info(){
        require_once(ABSPATH . '/wp-includes/class-snoopy.php');
        $request =  ICL_API_ENDOINT . 'websites/' . $this->site_id . '.xml?accesskey=' . $this->access_key;
        $c = new Snoopy();
        $url_parts = parse_url($request);
        $https = $url_parts['scheme']=='https';
        $c->fetch($request);            
        if((!$c->results || $c->timed_out) && $https){
            $c->fetch(str_replace('https://','http://',$request));  
        }
        $res = xml2array($c->results);
        return $res;
    }
    
    function js_scripts(){
        global $plugin_page;
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo ICLT_CT_PLUGIN_URL ?>/lib/popup.css" />
        <script src="<?php echo ICLT_CT_PLUGIN_URL ?>/lib/popup.js" type="text/javascript"></script>
        <script type="text/javascript">
            poparr_src_url  = '<?php echo ICLT_CT_PLUGIN_URL ?>/img';
            onload = getElementsWithTitles;            
        </script>        
        <?php if($plugin_page=='iclttc'): ?>
        <script type="text/javascript">
            jQuery(document).ready( function(){
                jQuery('#iclt_links_fixer').click(iclt_fix_translated_links);
            });
            
            function iclt_fix_translated_links(){
                jthis = jQuery(this);
                jthis.attr('value','<?php printf(__('Running: %02.2f %%'),0) ?>');
                jQuery.ajax({
                    type: "POST",
                    url: "<?php echo $_SERVER['REQUEST_URI'] ?>",
                    data: "iclttc_ajx_action=fix_links",
                    success: function(msg){
                        spl = msg.split('|');         
                        if(spl[0] > 0){
                            jthis.attr('value', spl[1]);
                            window.setTimeout(iclt_fix_translated_links,3000);
                        }else{
                            jthis.attr('value', '<?php echo __('Start') ?>');
                        }                        
                    }                    
                });                
            }        
        </script>
        <?php endif; ?>
        <?php
    }
    
    function ajax_actions($action){
        global $wpdb;
        switch($action){
            case 'fix_links':
                $all_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post','page')");
                $post = $wpdb->get_row("
                    SELECT SQL_CALC_FOUND_ROWS ID, post_content FROM {$wpdb->posts} 
                    WHERE post_type IN ('post','page') AND ID NOT IN 
                    (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_iclt_all_urls_translated')                
                ");  
                if(!$post){
                    echo '0|0';                    
                }else{              
                    $posts_left = $wpdb->get_var("SELECT FOUND_ROWS()") - 1;                
                    add_post_meta($post->ID,'_iclt_all_urls_translated',1);
                    $left_proc = round((1 - $posts_left/$all_posts)*100,2);
                    echo $posts_left . '|';
                    printf(__('Running: %02.2f %%'),$left_proc);
                }
                break;
            default: echo '';
        }
    }
    
}
      
                             
$icltc = new ICanLocalizeTBTranslate();

register_activation_hook( __FILE__, 'iclt_comments_translator_activate' );
function iclt_comments_translator_activate(){
    global $wpdb;    
    if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}comments_translated'") != $wpdb->prefix.'comments_translated'){
        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}comments_translated` (  
                `id` bigint(20) NOT NULL default '0',
                `translation` text NOT NULL,
                PRIMARY KEY  (`id`)                
            ) CHARACTER SET utf8 
        ");
    }    
    if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}comments_translation_requests'") != $wpdb->prefix.'comments_translation_requests'){
        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}comments_translation_requests` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `comment_id` bigint(20) NOT NULL ,
                `request_id` bigint(20) NOT NULL ,
                `translated` tinyint NOT NULL default '0',
                `date_request` datetime NOT NULL default '0000-00-00 00:00:00',
                `date_translated` datetime NOT NULL default '0000-00-00 00:00:00',                
                PRIMARY KEY  (`id`),
                INDEX (`request_id`) ,
                UNIQUE (`comment_id`)
            )     
        "); 
    }    
    
    $wpdb->query("
        CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}iclt_urls_map` (
          `id` bigint(20) NOT NULL auto_increment,
          `document_type` enum('post','page') NOT NULL default 'post',
          `post_id` bigint(20) NOT NULL default '0',
          `permalink` varchar(255) NOT NULL default '',
          `translated_id` bigint(20) NOT NULL default '0',
          `language` varchar(32) NOT NULL default '',
          PRIMARY KEY  (`id`),
          KEY `post_id_k` (`post_id`),
          KEY `translated_id_k` (`translated_id`),
          KEY `language` (`language`)
        ) ENGINE=MyISAM
        ");
    
    delete_option('iclt_tb_version');
    add_option('iclt_tb_version',ICLT_TB_CURRENT_VERSION,'',true);
    
}

?>