<?php
if(false == get_option('iclt_version')){
    update_option('iclt_version',ICLT_CURRENT_VERSION);
}  
  
$old_version = floatval(get_option('iclt_tb_version'));
$cur_version = floatval(ICLT_TB_CURRENT_VERSION);
if($cur_version == $old_version || !$old_version) return;

update_option('iclt_tb_version',ICLT_TB_CURRENT_VERSION);

$old_version = floatval(join('.',array_slice(explode('.',$old_version),0,2)));
/* 
VERSION 0.2 
*/
if($old_version < 0.2){
    $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_key='_ican_language' WHERE meta_key='_ican_translation'");
}

/* 
VERSION 1.1 
*/
if($old_version < 1.4){
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
}

?>
