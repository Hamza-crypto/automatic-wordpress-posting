<h1>AWP Stats</h1>
<?php
global $wpdb;
$rows = AWP_SQL::getRows($wpdb);
echo '<div id="awp_stats">';
if(count($rows) > 0){
	foreach ($rows as $row) {
		$points = explode('|', $row['awp_row']);
		echo '<div class="awp_stats-item">';
		echo '<h3>' . $row['awp_csv'] . '</h3>';
		$progress = 30;
		if(!is_array($points)){
			$start = $points;
			$finish = '?';
		}else if(is_array($points) && count($points) == 1){
			$start = $points[0];
			$finish = '?';
		}else{
			$start = $points[0];
			$finish = $points[1];
			$progress = (100 / $finish) * $start;
		}
		echo '<div><progress max="100" value="'.$progress.'"></progress>'.$start . ' of ' . $finish.'</div>';
		echo '</div>';
	}
}else{
	echo "<h3>Does not have CSV in progress</h3>";
}
echo '</div>';
//AWP_parse::doCron($wpdb);