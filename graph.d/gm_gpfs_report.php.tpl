<?php
include_once ("graph.d/RrdToolGraphBuilder.php");

function graph_gm_{$gpfs_fs_name}_report(&$rrdtool_graph) {
  $builder = new RrdToolGraphBuilder();
  $builder -> setTitle("{$gpfs_fs_name} mount");
  $builder -> setLowerLimit('0');
  $builder -> setVerticalLabel('nodes');
  $builder -> setFontSize(7);
  $builder -> addHeight(45);

  $series = new RrdToolGraphSeries();
  $series -> setDisplayAvg(false);
  $series -> setRrdFile("gm_{$gpfs_fs_name}_mounted_accessible.rrd");
  $series -> setColor("#00FF00");
  $series -> setTitle("mounted_accessible");
  $series -> setType(RrdToolGraphSeries::AREA);
  $builder -> addSeries(clone $series);

  $series -> setRrdFile('gm_{$gpfs_fs_name}_mounted_stale.rrd');
  $series -> setColor("#006400");
  $series -> setTitle("mounted_stale");
  $series -> setType(RrdToolGraphSeries::STOCK);
  $builder -> addSeries(clone $series);

  $series -> setRrdFile("gm_{$gpfs_fs_name}_remount_failure.rrd");
  $series -> setColor("#B8860B");
  $series -> setTitle("remount_failure");
  $builder -> addSeries(clone $series);

  $series -> setRrdFile("gm_{$gpfs_fs_name}_remount_success.rrd");
  $series -> setColor("#00BFFF");
  $series -> setTitle("remount_success");
  $builder -> addSeries(clone $series);

  $series -> setRrdFile("gm_{$gpfs_fs_name}_unmounted_error.rrd");
  $series -> setColor("#FF0000");
  $series -> setTitle("unmounted_error");
  $builder -> addSeries(clone $series);

  $series -> setRrdFile("gm_{$gpfs_fs_name}_unmounted_nodev.rrd");
  $series -> setColor("#FFB6C1");
  $series -> setTitle("umounted_nodev");
  $builder -> addSeries(clone $series);

  $series -> setRrdFile("gm_{$gpfs_fs_name}_unmounted_unusable.rrd");
  $series -> setColor("#FF4500");
  $series -> setTitle("unmounted_unusable");
  $builder -> addSeries(clone $series);

  $series -> setRrdFile("gm_{$gpfs_fs_name}_unknown.rrd");
  $series -> setColor("#2F4F4F");
  $series -> setTitle("unkown");
  $builder -> addSeries(clone $series);

  $builder -> build($rrdtool_graph);

  return $rrdtool_graph;
}
?>
