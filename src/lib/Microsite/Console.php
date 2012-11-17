<?php

namespace Microsite;

use \Microsite\Console\Color;

/**
 * Class for implementing console-based tools
 */
class Console
{

	public static function run()
	{
		echo Color::yellow("Microsite - 2.0");

		echo "\n\n";
	}
}

?>