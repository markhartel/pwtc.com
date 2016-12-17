<?php /* Smarty version 2.6.27, created on 2016-11-30 21:06:24
         compiled from CRM/common/customData.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'crmScope', 'CRM/common/customData.tpl', 1, false),)), $this); ?>
<?php $this->_tag_stack[] = array('crmScope', array('extensionKey' => "")); $_block_repeat=true;smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php echo '
<script type="text/javascript">
  (function($) {
    CRM.buildCustomData = function (type, subType, subName, cgCount, groupID, isMultiple) {
      var dataUrl = CRM.url(\'civicrm/custom\', {type: type}),
        prevCount = 1,
        fname = \'#customData\',
        storage = {};

      if (subType) {
        dataUrl += \'&subType=\' + subType;
      }

      if (subName) {
        dataUrl += \'&subName=\' + subName;
        $(\'#customData\' + subName).show();
      }
      else {
        $(\'#customData\').show();
      }
      if (groupID) {
        dataUrl += \'&groupID=\' + groupID;
      }

      '; ?>

      <?php if ($this->_tpl_vars['groupID']): ?>
        dataUrl += '&groupID=' + '<?php echo $this->_tpl_vars['groupID']; ?>
';
      <?php endif; ?>
      <?php if ($this->_tpl_vars['entityID']): ?>
        dataUrl += '&entityID=' + '<?php echo $this->_tpl_vars['entityID']; ?>
';
      <?php endif; ?>
      <?php echo '

      if (!cgCount) {
        cgCount = 1;
      }
      else if (cgCount >= 1) {
        prevCount = cgCount;
        cgCount++;
      }

      dataUrl += \'&cgcount=\' + cgCount;


      if (isMultiple) {
        fname = \'#custom_group_\' + groupID + \'_\' + prevCount;
        if ($(".add-more-link-" + groupID + "-" + prevCount).length) {
          $(".add-more-link-" + groupID + "-" + prevCount).hide();
        }
        else {
          $("#add-more-link-" + prevCount).hide();
        }
      }
      else if (subName && subName != \'null\') {
        fname += subName;
      }

      CRM.loadPage(dataUrl, {target: fname});
    };
  })(CRM.$);
</script>
'; ?>

<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_crmScope($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>