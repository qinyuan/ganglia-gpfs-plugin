<!DOCTYPE html>
<html>
<head>
  <meta charset="utf8" />
  <title>GPFS监控图</title>
  <link rel="stylesheet" href="css/index.css" type="text/css" />
</head>
<body>
  <div class="header">
    <div class="time">{$current_time}</div>
    <div class="comment">
      <div class="stateColorLabel">节点状态</div>
      <div class="stateColor">
      {foreach from=$state_colors item=state_color}
        {$state_color.desc}
        <span class="comment" style="background-color: {$state_color.code};">
        &nbsp;&nbsp;&nbsp;
        </span>
        &nbsp;&nbsp;&nbsp;
      {/foreach}
      </div>
    </div>
    <div class="comment">
      <div class="mountColorLabel">挂载状态</div>
      <div class="mountColor">
      {foreach from=$mount_colors item=mount_color}
        {$mount_color.desc}
        <span class="comment" style="background-color: {$mount_color.code};">
        &nbsp;&nbsp;&nbsp;
        </span>
        &nbsp;&nbsp;&nbsp;
      {/foreach}
      </div>
    </div>
  </div>
  <div class="charts">
  {foreach from=$clusters item=cluster}
    <div class="cluster">
      <div class="clusterName">{$cluster.name}</div>
      <div class="clusterChart">
        <div class="label">节点状态</div>
        <div class="hostsChart">
        {foreach from=$cluster.hosts item=host}
          <span class="host" title="{$host.state}" 
            style="background-color: {$host.state_color}">{$host.name}</span>
        {/foreach}
        </div>
      </div>
    {foreach from=$cluster.filesystems item=fs}
      <div class="clusterChart">
        <div class="label">{$fs.fs_name} 挂载状态</div>
        <div class="hostsChart">
        {foreach from=$fs.hosts item=host}
          <span class="host" title="{$host.mounted_state}" 
            style="background-color: {$host.color}">{$host.host_name}</span>
        {/foreach}
        </div>
      </div>
    {/foreach}
    </div>
  {/foreach}
  </div>
</body>
<script src="js/jquery.min.js"></script>
<script src="js/index.js"></script>
</html>
