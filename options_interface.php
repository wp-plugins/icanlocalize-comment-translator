<div class="wrap">
    <h2><img src="<?php echo ICLT_CT_PLUGIN_URL ?>/img/32_trans.png" height="32" width="32" alt="Icon" align="left" style="margin-right:6px;" /><?php echo __('ICanLocalize Comment Translator Settings') ?></h2> 
    <form action="" method="post">
    <?php wp_nonce_field('update-incalocalize-comments') ?>
    
    <?php if(0): ?>
    <div id="message" class="error">
    <p><b><?php echo __('Error') ?>:</b> <?php echo $e ?></p>
    </div>    
    <?php endif; ?>
    <table class="form-table">
        <tbody>        
            <tr valign="top">
                <th scope="row">&nbsp;</th>
                <td>
                    <div style="background:url(<?php echo ICLT_CT_PLUGIN_URL ?>/img/<?php echo $help_icon_image ?>);background-repeat:no-repeat;padding:4px 4px 4px 32px;border:solid 1px #eee;float:left;background-position:4px 4px;height:24px;<?php echo $help_bg_style ?>">
                    <?php if(isset($_GET['iclt_error'])) echo $_GET['iclt_error'] . '&nbsp;' ?><?php echo __('Need help? Visit'); ?> <a href="http://sitepress.org/wordpress-translation/using-icanlocalize-translator/">ICanLocalize Translation Getting Started Guide</a>.
                    </div>
                </td>
            </tr>            
            <tr valign="top">
                <th scope="row"><?php echo __('Site ID') ?></th>
                <td><input id="iclt_site_id" name="iclt_site_id" class="code" type="text" size="20" value="<?php echo $this->site_id ?>" /></td>
            </tr>    
            <tr valign="top">
                <th scope="row"><?php echo __('Access Key') ?></th>
                <td><input id="iclt_access_key" name="iclt_access_key" class="code" type="text" size="40" value="<?php echo $this->access_key ?>" /></td>
            </tr>  
            <tr valign="top">
                <th scope="row"><?php echo __('Comments translation') ?></th>
                <td>
                    <style>
                    #iclt_user_comments_settings{border-collapse: collapse;margin-left:4px;}
                    #iclt_user_comments_settings th{ border:none; padding: 3px 5px 2px 5px;}
                    #iclt_user_comments_settings td{ border:1px solid #FFF ; padding: 3px 5px 2px 5px;}
                    </style>                    
                    <table id="iclt_user_comments_settings" cellpadding="0" cellspacing="0" width="300">
                    <th><?php echo __('User login') ?></th>
                    <th><?php echo __('Translate comments') ?></th>
                    <th><?php echo __('Translate replies') ?></th>                    
                    <?php 
                        global $userdata;
                        $users = get_editable_authors($userdata->ID);
                    ?>
                    <?php foreach((array)$users as $u): ?>
                        <?php 
                            $enable_comments_translation = get_usermeta($u->ID,'enable_comments_translation',true);
                            $enable_replies_translation = get_usermeta($u->ID,'enable_replies_translation',true);
                        ?>
                        <tr>
                        <td><a href="user-edit.php?user_id=<?php echo $u->ID?>"><?php echo $u->user_login ?></a></td>
                        <td width="5%" align="center"><input type="checkbox" name="enable_comments_translation[<?php echo $u->ID ?>]" value="1" 
                            <?php if($enable_comments_translation): ?>checked="checked"<?php endif?> /></td>
                        <td width="5%" align="center"><input type="checkbox" name="enable_replies_translation[<?php echo $u->ID ?>]" value="1" 
                            <?php if($enable_replies_translation): ?>checked="checked"<?php endif?> /></td>
                        <tr>
                    <?php endforeach; ?>
                    </table>
                </td>
            </tr>           
            <?php if($blog_langs): ?>   
            <tr valign="top">
                <th scope="row"><?php echo __('Select blog language') ?></th>
                <td>
                    <select name="blog_language">
                        <?php foreach($blog_langs as $b): ?>
                        <option value="<?php echo $b ?>" <?php if($b == $blog_lang): ?>selected="selected"<?php endif?>><?php echo $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>                 
            <?php endif; ?>           
            <tr valign="top">
                <th scope="row"><?php echo __('Plugin version') ?></th>
                <td><?php echo get_option('iclt_tb_version') ?></td>
            </tr>                
            
        </tbody>
    </table>
    
    <p class="submit">    
    <input class="button" type="submit" value="<?php echo __('Save Changes') ?>" name="Submit"/>
    </p>
    </form>
    
    <div>
        <p><b><?php echo __('Links translation')?>:</b></p>
        <p><?php echo __('Check for links to the original blog and replace them with the corresponding links in the current blog'); ?></p>
        <p> 
            <?php echo sprintf(__('%s urls in the urls map table'), $links_map_size)?> 
            <input id="iclt_links_fixer" class="button" type="button" value="<?php echo __('Start') ?>" 
                <?php if(!$links_map_size):?>disabled="disabled"<?php endif ?> />
        </p>
    </div>
</div>