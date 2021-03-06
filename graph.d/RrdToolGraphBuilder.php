<?php
class RrdToolGraphSeries {
  const AREA = 1;
  const STOCK = 2;
  const LINE = 3;
  private $color = "#000000";
  private $rrd_root;
  private $rrd_file;
  private $type;
  private $title;
  private $titleLength;
  private $unit = 's';
  private $displayNow = true;
  private $displayMin = true;
  private $displayAvg = true;
  private $displayMax = true;
  
  function __construct(){
    global $rrd_dir;
    if (!file_exists($rrd_dir)) {
      throw new Exception("invalid direcotry paht $rrd_dir");
    }
    if ($rrd_dir[strlen($rrd_dir) - 1] != "/") {
      $rrd_dir .= "/";
    }
    $this -> rrd_root = $rrd_dir;
  }
  
  function setDisplayNow($bool) {
  	$this -> displayNow = $bool;
  }
  
  function setDisplayMin($bool) {
  	$this -> displayMin = $bool;
  }
  
  function setDisplayAvg($bool) {
  	$this -> displayAvg = $bool;
  }
  
  function setDisplayMax($bool) {
  	$this -> displayMax = $bool;
  }
  
  function getTitleLength() {
  	return $this -> titleLength;
  }

  function setTitle($title) {
    $this -> title = $title;
	
  	$title_length = strlen($title);
  	if ($title_length > $this -> titleLength) {
  	  $this -> titleLength = $title_length;
  	}
  }

  function setTitleLength($titleLegth) {
    $this -> titleLength = $titleLegth;
  }

  function setColor($color) {
    $this -> color = $color;
  }

  function setRrdFile($rrd_file) {
    $this -> rrd_file = $rrd_file;
  }

  function setType($type) {
    $valid_values = array(self::AREA, self::STOCK, self::LINE);
    if (!in_array($type, $valid_values)) {
      $errorInfo = 'invalid $type value, the $type must in (';
      $errorInfo .= join($valid_values, ', ');
      $errorInfo .= ')';
      throw new Exception($errorInfo);
    }
    $this -> type = $type;
  }
  
  function setPercent($is_percent) {
  	$this -> unit = $is_percent ? '%' : 's;';
  }

  function validateRrdDir() {
    $fullRrdFilePath = $this -> rrd_root . $this -> rrd_file;
    return file_exists($fullRrdFilePath); 
  }

  function __toString() {
    if (!$this -> validateRrdDir()) {
      return "";
    }
    $fullRrdFilePath = $this -> rrd_root . $this -> rrd_file;
    $graph_name = str_replace(".rrd", "", $this -> rrd_file);
    $str = "DEF:'$graph_name'='$fullRrdFilePath':'sum':AVERAGE ";

    if ($this -> type == self::AREA) {
      $str .= "AREA";
    } else if ($this -> type == self::STOCK) {
      $str .= "STACK";
	} else if ($this -> type == self::LINE) {
	  $str .= "LINE2";
    } else {
      throw new Exception("unexpected Error!");
    }

    $str .= ":'$graph_name'$this->color:'$this->title\\g' ";

    $pos = $graph_name . "_pos";
    $str .= "CDEF:$pos=$graph_name,0,INF,LIMIT ";

    $last = $graph_name . "_last";
    $min = $graph_name . "_min";
    $avg = $graph_name . "_avg";
    $max = $graph_name . "_max";

    $str .= "VDEF:$last=$pos,LAST ";
    $str .= "VDEF:$min=$pos,MINIMUM ";
    $str .= "VDEF:$avg=$pos,AVERAGE ";
    $str .= "VDEF:$max=$pos,MAXIMUM ";

    $space = "";
	$spaceNum = $this -> titleLength - strlen($this -> title);
    if ($spaceNum > 0) {
      $space = str_repeat(" ", $spaceNum);
    }
	
	if ($this -> displayNow) {
      $str .= "GPRINT:'$last':'{$space} Now\:%5.1lf%{$this -> unit}' ";
	}
	if ($this -> displayMin) {
      $str .= "GPRINT:'$min':'Min\:%5.1lf%{$this -> unit}' ";
	}
	if ($this -> displayAvg) {
      $str .= "GPRINT:'$avg':'Avg\:%5.1lf%{$this -> unit}' ";
	}
	if ($this -> displayMax) {
      $str .= "GPRINT:'$max':'Max\:%5.1lf%{$this -> unit}\l' ";
	}

    return $str;
  }
}

class RrdToolGraphBuilder {

  private $rrdtool_graph;
  private $title;
  private $upper_limit = null;
  private $lower_limit = '0';
  private $vertical_label;
  private $add_height = 0;
  private $font_size;
  private $slope_mode = true;
  private $rigid = true;
  private $series = array();
  private $max_series_title_length = 0;

  function addSeries(RrdToolGraphSeries $series) {
  	if ($series -> getTitleLength() > $this -> max_series_title_length) {
  	  $this -> max_series_title_length = $series -> getTitleLength();
	  for($i =0 ,$len = count($this -> series) ; $i< $len ; $i++) {
	  	$this -> series[$i] -> setTitleLength($this -> max_series_title_length);
	  }
  	}
  	$this -> series[] = $series;
  }

  function addHeight($height_to_add) {
    $this -> add_height += $height_to_add;
  }

  function build(&$rrdtool_graph) {
    if ($this -> title) {
      $rrdtool_graph['title'] = $this -> title;
    }
    if ($this -> upper_limit != null) {
      $rrdtool_graph['upper-limit'] = $this -> upper_limit;
    }
    if ($this -> lower_limit != null) {
      $rrdtool_graph['lower-limit'] = $this -> lower_limit;
    }
    if ($this -> vertical_label) {
      $rrdtool_graph['vertical-label'] = $this -> vertical_label;
    }

    if (!isset($rrdtool_graph['extras'])) {
      $rrdtool_graph['extras'] = '';
    }
    if ($this -> font_size) {
      $rrdtool_graph['extras'] = ' --font LEGEND:' . $this -> font_size;
    }
    if ($this -> rigid) {
      $rrdtool_graph['extras'] .= " --rigid";
    }

    if (!isset($rrdtool_graph['height'])) {
      $rrdtool_graph['height'] = 0;
    }
    $rrdtool_graph['height'] += $this -> add_height;

    $rrdtool_graph['series'] = '';
    foreach ($this -> series as $s) {
      $rrdtool_graph['series'] .= $s;
    }

    return $this -> rrdtool_graph;
  }

  function setTitle($title) {
    $this -> title = $title;
  }

  function setUpperLimit($upper_limit) {
    $this -> upper_limit = $upper_limit;
  }

  function setLowerLimit($lower_limit) {
    $this -> lower_limit = $lower_limit;
  }

  function setVerticalLabel($vertical_label) {
    $this -> vertical_label = $vertical_label;
  }

  function setFontSize($font_size) {
    $this -> font_size = $font_size;
  }

  function setSlopeMode($slope_mode) {
    $this -> slope_mode = $slope_mode;
  }

  function setRigid($rigid) {
    $this -> rigid = $rigid;
  }

}

class ColorFactory{
    private $index = 0;
    private $colors = array(
        '#FFFF00',
        '#00FF00',
        '#0000FF',
        '#7700BB',
        '#006400',
        '#B8860B',
        '#00BFFF',
        '#FF0000',
        '#FFB6C1',
        '#FF4500',
        '#2F4F4F',
        '#88eF34',
        '#24E7E7',
        '#A8A8A8',
        '#e827e8',
    );
    private $len;

    function __construct() {
        $this -> len = count($this -> colors);
    }

    function getInstance() {
        if($this -> index >= $this -> len) {
            $this -> index = 0;
        }
        return $this -> colors[$this -> index++];
    }
}
?>
