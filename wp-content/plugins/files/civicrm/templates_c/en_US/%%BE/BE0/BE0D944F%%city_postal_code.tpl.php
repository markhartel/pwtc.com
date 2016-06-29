<?php /* Smarty version 2.6.27, created on 2016-06-13 01:47:02
         compiled from CRM/Contact/Form/Edit/Address/city_postal_code.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'crmScope', 'CRM/Contact/Form/Edit/Address/city_postal_code.tpl', 1, false),array('function', 'help', 'CRM/Contact/Form/Edit/Address/city_postal_code.tpl', 42, false),)), $this); ?>
<?php $this->_tag_stack[] = array('crmScope', array('extensionKey' => "")); $_block_repeat=true;smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><tr><td colspan="3" style="padding:0;">
<table class="crm-address-element">
<tr>
    <?php if (! empty ( $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['city'] )): ?>
       <td>
          <?php echo $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['city']['label']; ?>
<br />
          <?php echo $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['city']['html']; ?>

       </td>
    <?php endif; ?>
    <?php if (! empty ( $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['postal_code'] )): ?>
       <td>
          <?php echo $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['postal_code']['label']; ?>
<br />
          <?php echo $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['postal_code']['html']; ?>

       </td>
      <?php if (! empty ( $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['postal_code_suffix'] )): ?>
          <td>
            <?php echo $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['postal_code_suffix']['label']; ?>
 <?php echo smarty_function_help(array('id' => "id-postal-code-suffix",'file' => "CRM/Contact/Form/Contact.hlp"), $this);?>
<br/>
            <?php echo $this->_tpl_vars['form']['address'][$this->_tpl_vars['blockId']]['postal_code_suffix']['html']; ?>

          <td>
      <?php endif; ?>
    <?php endif; ?>
    <td colspan="2">&nbsp;&nbsp;</td>
</tr>
</table>
</td></tr>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>