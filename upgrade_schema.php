<?php
if(false == get_option('iclt_version')){
    update_option('iclt_version',ICLT_CURRENT_VERSION);
}  
  
$old_version = floatval(get_option('iclt_tb_version'));
$cur_version = floatval(ICLT_TB_CURRENT_VERSION);

if($cur_version == $old_version || !$old_version) return;

update_option('iclt_tb_version',ICLT_TB_CURRENT_VERSION);

/* 
VERSION 0.2 
*/
if($old_version < 0.2){
    $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_key='_ican_language' WHERE meta_key='_ican_translation'");
}
?>
