<?php /* Smarty version Smarty-3.1.16, created on 2014-05-03 15:45:28
         compiled from "./templates/index.tpl" */ ?>
<?php /*%%SmartyHeaderCode:16142559175360c56368b209-08468446%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'c0360d049dff10f364dfc53ba2cc3958abf6ee6d' => 
    array (
      0 => './templates/index.tpl',
      1 => 1399103094,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '16142559175360c56368b209-08468446',
  'function' => 
  array (
  ),
  'version' => 'Smarty-3.1.16',
  'unifunc' => 'content_5360c563828658_68090190',
  'variables' => 
  array (
    'current_time' => 0,
    'state_colors' => 0,
    'state_color' => 0,
    'mount_colors' => 0,
    'mount_color' => 0,
    'clusters' => 0,
    'cluster' => 0,
    'host' => 0,
    'fs' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5360c563828658_68090190')) {function content_5360c563828658_68090190($_smarty_tpl) {?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf8" />
  <title>GPFS监控图</title>
  <link rel="stylesheet" href="css/index.css" type="text/css" />
</head>
<body>
  <div class="header">
    <div class="time"><?php echo $_smarty_tpl->tpl_vars['current_time']->value;?>
</div>
    <div class="comment">
      <div class="stateColorLabel">节点状态</div>
      <div class="stateColor">
      <?php  $_smarty_tpl->tpl_vars['state_color'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['state_color']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['state_colors']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['state_color']->key => $_smarty_tpl->tpl_vars['state_color']->value) {
$_smarty_tpl->tpl_vars['state_color']->_loop = true;
?>
        <?php echo $_smarty_tpl->tpl_vars['state_color']->value['desc'];?>

        <span class="comment" style="background-color: <?php echo $_smarty_tpl->tpl_vars['state_color']->value['code'];?>
;">
        &nbsp;&nbsp;&nbsp;
        </span>
        &nbsp;&nbsp;&nbsp;
      <?php } ?>
      </div>
    </div>
    <div class="comment">
      <div class="mountColorLabel">挂载状态</div>
      <div class="mountColor">
      <?php  $_smarty_tpl->tpl_vars['mount_color'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['mount_color']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['mount_colors']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['mount_color']->key => $_smarty_tpl->tpl_vars['mount_color']->value) {
$_smarty_tpl->tpl_vars['mount_color']->_loop = true;
?>
        <?php echo $_smarty_tpl->tpl_vars['mount_color']->value['desc'];?>

        <span class="comment" style="background-color: <?php echo $_smarty_tpl->tpl_vars['mount_color']->value['code'];?>
;">
        &nbsp;&nbsp;&nbsp;
        </span>
        &nbsp;&nbsp;&nbsp;
      <?php } ?>
      </div>
    </div>
  </div>
  <div class="charts">
  <?php  $_smarty_tpl->tpl_vars['cluster'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['cluster']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['clusters']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['cluster']->key => $_smarty_tpl->tpl_vars['cluster']->value) {
$_smarty_tpl->tpl_vars['cluster']->_loop = true;
?>
    <div class="cluster">
      <div class="clusterName"><?php echo $_smarty_tpl->tpl_vars['cluster']->value['name'];?>
</div>
      <div class="clusterChart">
        <div class="label">节点状态</div>
        <div class="hostsChart">
        <?php  $_smarty_tpl->tpl_vars['host'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['host']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['cluster']->value['hosts']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['host']->key => $_smarty_tpl->tpl_vars['host']->value) {
$_smarty_tpl->tpl_vars['host']->_loop = true;
?>
          <span class="host" title="<?php echo $_smarty_tpl->tpl_vars['host']->value['state'];?>
" 
            style="background-color: <?php echo $_smarty_tpl->tpl_vars['host']->value['state_color'];?>
"><?php echo $_smarty_tpl->tpl_vars['host']->value['name'];?>
</span>
        <?php } ?>
        </div>
      </div>
    <?php  $_smarty_tpl->tpl_vars['fs'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['fs']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['cluster']->value['filesystems']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['fs']->key => $_smarty_tpl->tpl_vars['fs']->value) {
$_smarty_tpl->tpl_vars['fs']->_loop = true;
?>
      <div class="clusterChart">
        <div class="label"><?php echo $_smarty_tpl->tpl_vars['fs']->value['fs_name'];?>
 挂载状态</div>
        <div class="hostsChart">
        <?php  $_smarty_tpl->tpl_vars['host'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['host']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['fs']->value['hosts']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['host']->key => $_smarty_tpl->tpl_vars['host']->value) {
$_smarty_tpl->tpl_vars['host']->_loop = true;
?>
          <span class="host" title="<?php echo $_smarty_tpl->tpl_vars['host']->value['mounted_state'];?>
" 
            style="background-color: <?php echo $_smarty_tpl->tpl_vars['host']->value['color'];?>
"><?php echo $_smarty_tpl->tpl_vars['host']->value['host_name'];?>
</span>
        <?php } ?>
        </div>
      </div>
    <?php } ?>
    </div>
  <?php } ?>
  </div>
</body>
<script src="js/jquery.min.js"></script>
<script src="js/index.js"></script>
</html>
<?php }} ?>
