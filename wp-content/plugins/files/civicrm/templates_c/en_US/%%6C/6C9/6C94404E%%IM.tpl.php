<?php /* Smarty version 2.6.27, created on 2016-11-30 21:04:28
         compiled from CRM/Contact/Page/Inline/IM.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'crmScope', 'CRM/Contact/Page/Inline/IM.tpl', 1, false),array('block', 'ts', 'CRM/Contact/Page/Inline/IM.tpl', 28, false),)), $this); ?>
<?php $this->_tag_stack[] = array('crmScope', array('extensionKey' => "")); $_block_repeat=true;smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><div id="crm-im-content" <?php if ($this->_tpl_vars['permission'] == 'edit'): ?> class="crm-inline-edit" data-edit-params='{"cid": "<?php echo $this->_tpl_vars['contactId']; ?>
", "class_name": "CRM_Contact_Form_Inline_IM"}'<?php endif; ?>>
  <div class="crm-clear crm-inline-block-content" <?php if ($this->_tpl_vars['permission'] == 'edit'): ?>title="<?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Add or edit IM<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>"<?php endif; ?>>
    <?php if ($this->_tpl_vars['permission'] == 'edit'): ?>
      <div class="crm-edit-help">
        <span class="crm-i fa-pencil"></span> <?php if (empty ( $this->_tpl_vars['im'] )): ?><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Add IM<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?><?php else: ?><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Add or edit IM<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?><?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if (empty ( $this->_tpl_vars['im'] )): ?>
      <div class="crm-summary-row">
        <div class="crm-label"><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>IM<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></div>
        <div class="crm-content"></div>
      </div>
    <?php endif; ?>
    <?php $_from = $this->_tpl_vars['im']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
?>
      <?php if ($this->_tpl_vars['item']['name'] || $this->_tpl_vars['item']['provider']): ?>
        <?php if ($this->_tpl_vars['item']['name']): ?>
        <div class="crm-summary-row <?php if ($this->_tpl_vars['item']['is_primary'] == 1): ?> primary<?php endif; ?>">
          <div class="crm-label"><?php echo $this->_tpl_vars['item']['provider']; ?>
&nbsp;(<?php echo $this->_tpl_vars['item']['location_type']; ?>
)</div>
          <div class="crm-content crm-contact_im"><?php echo $this->_tpl_vars['item']['name']; ?>
</div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endforeach; endif; unset($_from); ?>
   </div>
</div>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>