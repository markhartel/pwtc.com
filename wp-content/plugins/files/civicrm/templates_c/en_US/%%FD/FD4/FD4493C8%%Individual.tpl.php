<?php /* Smarty version 2.6.27, created on 2016-06-13 01:47:01
         compiled from CRM/Contact/Form/Edit/Individual.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'crmScope', 'CRM/Contact/Form/Edit/Individual.tpl', 1, false),array('block', 'ts', 'CRM/Contact/Form/Edit/Individual.tpl', 51, false),array('function', 'crmURL', 'CRM/Contact/Form/Edit/Individual.tpl', 32, false),array('function', 'help', 'CRM/Contact/Form/Edit/Individual.tpl', 115, false),)), $this); ?>
<?php $this->_tag_stack[] = array('crmScope', array('extensionKey' => "")); $_block_repeat=true;smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><script type="text/javascript">
<?php echo '
CRM.$(function($) {
  '; ?>

    var cid = "<?php echo $this->_tpl_vars['contactId']; ?>
",
      viewIndividual = "<?php echo CRM_Utils_System::crmURL(array('p' => 'civicrm/contact/view','q' => 'reset=1&cid=','h' => 0), $this);?>
",
      checkSimilar = <?php echo $this->_tpl_vars['checkSimilar']; ?>
,
      lastnameMsg;
  <?php echo '
  if ($(\'#contact_sub_type *\').length == 0) {//if they aren\'t any subtype we don\'t offer the option
    $(\'#contact_sub_type\').parent().hide();
  }
  if (cid.length || !checkSimilar) {
   return;//no dupe check if this is a modif or if checkSimilar is disabled (contact_ajax_check_similar in civicrm_setting table)
  }
  $(\'#last_name\').change(function() {
    // Close msg if it exists
    lastnameMsg && lastnameMsg.close && lastnameMsg.close();
    if (this.value == \'\') return;
    CRM.api3(\'contact\', \'get\', {
      sort_name: $(\'#last_name\').val(),
      contact_type: \'Individual\',
      \'return\': \'display_name,sort_name,email\'
    }).done(function(data) {
      var title = data.count == 1 ? '; ?>
"<?php $this->_tag_stack[] = array('ts', array('escape' => 'js')); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Similar Contact Found<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>" : "<?php $this->_tag_stack[] = array('ts', array('escape' => 'js')); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Similar Contacts Found<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>"<?php echo ',
        msg = "<em>'; ?>
<?php $this->_tag_stack[] = array('ts', array('escape' => 'js')); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>If the person you were trying to add is listed below, click their name to view or edit their record<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?><?php echo ':</em>";
      if (data.is_error == 1 || data.count == 0) {
        return;
      }
      msg += \'<ul class="matching-contacts-actions">\';
      $.each(data.values, function(i, contact) {
        contact.email = contact.email || \'\';
        msg += \'<li><a href="\'+viewIndividual+contact.id+\'">\'+ contact.display_name +\'</a> \'+contact.email+\'</li>\';
      });
      msg += \'</ul>\';
      lastnameMsg = CRM.alert(msg, title);
      $(\'.matching-contacts-actions a\').click(function() {
        // No confirmation dialog on click
        $(\'[data-warn-changes=true]\').attr(\'data-warn-changes\', \'false\');
      });
    });
  });
});
</script>
'; ?>


<table class="form-layout-compressed">
  <tr>
    <?php if ($this->_tpl_vars['form']['prefix_id']): ?>
    <td>
      <?php echo $this->_tpl_vars['form']['prefix_id']['label']; ?>
<br/>
      <?php echo $this->_tpl_vars['form']['prefix_id']['html']; ?>

    </td>
    <?php endif; ?>
    <?php if ($this->_tpl_vars['form']['formal_title']): ?>
    <td>
      <?php echo $this->_tpl_vars['form']['formal_title']['label']; ?>
<br/>
      <?php echo $this->_tpl_vars['form']['formal_title']['html']; ?>

    </td>
    <?php endif; ?>
    <?php if ($this->_tpl_vars['form']['first_name']): ?>
    <td>
      <?php echo $this->_tpl_vars['form']['first_name']['label']; ?>
<br />
      <?php echo $this->_tpl_vars['form']['first_name']['html']; ?>

    </td>
    <?php endif; ?>
    <?php if ($this->_tpl_vars['form']['middle_name']): ?>
    <td>
      <?php echo $this->_tpl_vars['form']['middle_name']['label']; ?>
<br />
      <?php echo $this->_tpl_vars['form']['middle_name']['html']; ?>

    </td>
    <?php endif; ?>
    <?php if ($this->_tpl_vars['form']['last_name']): ?>
    <td>
      <?php echo $this->_tpl_vars['form']['last_name']['label']; ?>
<br />
      <?php echo $this->_tpl_vars['form']['last_name']['html']; ?>

    </td>
    <?php endif; ?>
    <?php if ($this->_tpl_vars['form']['suffix_id']): ?>
    <td>
      <?php echo $this->_tpl_vars['form']['suffix_id']['label']; ?>
<br/>
      <?php echo $this->_tpl_vars['form']['suffix_id']['html']; ?>

    </td>
    <?php endif; ?>
  </tr>

  <tr>
    <td colspan="2">
      <?php echo $this->_tpl_vars['form']['employer_id']['label']; ?>
&nbsp;<?php echo smarty_function_help(array('id' => "id-current-employer",'file' => "CRM/Contact/Form/Contact.hlp"), $this);?>
<br />
      <?php echo $this->_tpl_vars['form']['employer_id']['html']; ?>

    </td>
    <td>
      <?php echo $this->_tpl_vars['form']['job_title']['label']; ?>
<br />
      <?php echo $this->_tpl_vars['form']['job_title']['html']; ?>

    </td>
    <td>
      <?php echo $this->_tpl_vars['form']['nick_name']['label']; ?>
<br />
      <?php echo $this->_tpl_vars['form']['nick_name']['html']; ?>

    </td>
    <td>
      <?php if ($this->_tpl_vars['buildContactSubType']): ?>
      <?php echo $this->_tpl_vars['form']['contact_sub_type']['label']; ?>
<br />
      <?php echo $this->_tpl_vars['form']['contact_sub_type']['html']; ?>

      <?php endif; ?>
    </td>
  </tr>
</table>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>