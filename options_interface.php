<div class="wrap">
    <h2>ICanLocalize Settings</h2>
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
            <tr valign="top">
                <th scope="row"><?php echo __('Plugin version') ?></th>
                <td><?php echo get_option('iclt_tb_version') ?></td>
            </tr>                
            
        </tbody>
    </table>
    
    <p class="submit">    
    <input class="button" type="submit" value="Save Changes" name="Submit"/>
    </p>
    </form>
</div>