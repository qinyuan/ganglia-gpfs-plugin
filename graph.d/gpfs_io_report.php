<?php
include_once ("graph.d/RrdToolGraphBuilder.php");

function graph_gpfs_io_report(&$rrdtool_graph) {
  $builder = new RrdToolGraphBuilder();
  $builder -> setTitle("gpfs io");
  $builder -> setLowerLimit('0');
  $builder -> setVerticalLabel('byte/sec');
  $builder -> setFontSize(7);
  $builder -> addHeight(45);

  $series = new RrdToolGraphSeries();
  $filesystems = get_gpfs_filesystems();
  $colorFactory = new ColorFactory();

  $first = true;
  foreach ($filesystems as $fs) {
    $series -> setRrdFile("gpfs_io_rps_$fs.rrd");
    #$series -> setColor("#FFFF00");
    $series -> setColor($colorFactory -> getInstance());
    $series -> setTitle("$fs rps");
    if ($first) {
      $series -> setType(RrdToolGraphSeries::AREA);
      $first = false;
    }
    $builder -> addSeries(clone $series);
    
    $series -> setRrdFile("gpfs_io_wps_$fs.rrd");
    #$series -> setColor("#00FF00");
    $series -> setColor($colorFactory -> getInstance());
    $series -> setTitle("$fs wps");
    $series -> setType(RrdToolGraphSeries::STOCK);
    $builder -> addSeries(clone $series);
  }

  $builder -> build($rrdtool_graph);
  return $rrdtool_graph;
}
?>
