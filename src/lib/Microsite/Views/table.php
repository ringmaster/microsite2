<?php
echo "<table>\n";
foreach($results as $result) {
	echo "\t<tr>\n";
	foreach($result->get_fields() as $key) {
		echo "\t\t<td>{$result->$key}</td>\n";
	}
	echo "\t</tr>\n";
}
echo "</table>\n";
?>