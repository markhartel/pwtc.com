<?php /* Smarty version 2.6.27, created on 2016-06-13 01:45:50
         compiled from CRM/common/success.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'crmScope', 'CRM/common/success.tpl', 1, false),array('block', 'ts', 'CRM/common/success.tpl', 35, false),array('function', 'docURL', 'CRM/common/success.tpl', 45, false),array('function', 'crmURL', 'CRM/common/success.tpl', 72, false),)), $this); ?>
<?php $this->_tag_stack[] = array('crmScope', array('extensionKey' => "")); $_block_repeat=true;smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php if ($this->_tpl_vars['config']->userSystem->is_drupal != '1'): ?>
    <h2><?php echo $this->_tpl_vars['pageTitle']; ?>
</h2>
<?php endif; ?>
<?php if (! $this->_tpl_vars['upgraded']): ?>
  <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #FFFFE3; border: 1px #F8FF00 solid; color: black;">
    <form method="post">
        <p>
          <span class="crm-status-icon info"> </span>
          <?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['currentVersion'],'2' => $this->_tpl_vars['newVersion'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>The database will be upgraded from %1 to %2.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
        </p>
        <?php if ($this->_tpl_vars['preUpgradeMessage']): ?>
            <div style="border: 2px solid #E43D2B; background-color: rgba(228, 61, 43, 0.08); padding: 10px; margin-bottom: 15px;">
              <span class="crm-status-icon"></span>
              <strong style="vertical-align: middle; font-size: 1.2em;"><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Warning:<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></strong>
              <?php echo $this->_tpl_vars['preUpgradeMessage']; ?>

            </div>
        <?php endif; ?>
        <p><strong><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Back up your database before continuing.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></strong>
            <?php ob_start(); ?><?php echo smarty_function_docURL(array('page' => 'Installation and Upgrades','text' => 'Upgrade Documentation','style' => "text-decoration: underline;",'resource' => 'wiki'), $this);?>
<?php $this->_smarty_vars['capture']['default'] = ob_get_contents();  $this->assign('docLink', ob_get_contents());ob_end_clean(); ?>
            <?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['docLink'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>This process may change your database structure and values. In case of emergency you may need to revert to a backup. For more detailed information, refer to the %1.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></p>
        <input type="hidden" name="action" value="begin" />
        <button type="submit" class="crm-button" name="upgrade" onclick="return confirm('<?php $this->_tag_stack[] = array('ts', array('escape' => 'js')); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Are you sure you are ready to upgrade now?<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>');" >
          <i class="crm-i fa-rocket"></i>
          <?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Upgrade Now<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
        </button>&nbsp;&nbsp;
        <a class="button cancel crm-form-submit" href="<?php echo $this->_tpl_vars['cancelURL']; ?>
">
          <i class="crm-i fa-times"></i>
          <?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Cancel<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
        </a>
    </form>
  </div>

<?php else: ?>
    <div class="crm-container" style="margin-top: 2em; padding: 1em; background-color: #EEFFEE; border: 1px #070 solid; color: black;">
      <div class="bold" style="padding: 1em; background-color: rgba(255, 255, 255, 0.76);">
        <p>
          <img style="display:block; float:left; width:40px; margin-right:10px;" src="<?php echo $this->_tpl_vars['config']->resourceBase; ?>
i/logo_lg.png">
          <?php $this->_tag_stack[] = array('ts', array('1' => "https://civicrm.org/core-team",'2' => "https://civicrm.org/providers/contributors",'3' => "https://civicrm.org/become-a-member?src=ug&sid=".($this->_tpl_vars['sid']))); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Thank you for upgrading to 4.7, the latest version of CiviCRM. Packed with new features and improvements, this release was made possible by both the <a href="%1">CiviCRM Core Team</a> and an incredible group of <a href="%2">contributors</a>, combined with the financial support of CiviCRM Members and Partners, without whom the project could not exist. We invite you to join their ranks by <a href="%3">becoming a member of CiviCRM today</a>. There is no better way to say thanks than to support those that have made CiviCRM 4.7 possible. <a href="%3">Join today</a>.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
        </p>
      </div>
      <p><span class="crm-status-icon success"> </span><?php echo $this->_tpl_vars['message']; ?>
</p>
      <?php if ($this->_tpl_vars['afterUpgradeMessage']): ?>
        <h3><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Important Notes<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></h3>
        <p><?php echo $this->_tpl_vars['afterUpgradeMessage']; ?>
</p>
      <?php endif; ?>
      <p><a href="<?php echo CRM_Utils_System::crmURL(array('p' => 'civicrm/dashboard','q' => 'reset=1'), $this);?>
" title="<?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>CiviCRM home page<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>" style="text-decoration: underline;"><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Return to CiviCRM home page.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></a></p>
    </div>
<?php endif; ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>