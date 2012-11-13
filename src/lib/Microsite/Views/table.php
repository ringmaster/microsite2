<?php
echo "<table>\n";
foreach($results as $result) {
	echo "\t<tr>\n";
	foreach($result as $key => $value) {
		echo "\t\t<td>{$value}</td>\n";
	}
	echo "\t</tr>\n";
}
echo "</table>\n";
?>