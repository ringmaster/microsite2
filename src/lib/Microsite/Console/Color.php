<?php

namespace Microsite\Console;

class Color {

	public static $foregrounds = [
		'black' => '0;30',
		'dkgray' => '1;30',
		'blue' => '0;34',
		'ltblue' => '1;34',
		'green' => '0;32',
		'ltgreen' => '1;32',
		'cyan' => '0;36',
		'ltcyan' => '1;36',
		'red' => '0;31',
		'ltred' => '1;31',
		'purple' => '0;35',
		'ltpurple' => '1;35',
		'brown' => '0;33',
		'yellow' => '1;33',
		'ltgray' => '0;37',
		'white' => '1;37',
	];

	public static $backgrounds = [
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'yellow' => '43',
		'blue' => '44',
		'magenta' => '45',
		'cyan' => '46',
		'ltgray' => '47',
	];

	public static function black($string, $background = '') {
		return self::color($string, 'black', $background);
	}

	public static function dkgray($string, $background = '') {
		return self::color($string, 'dkgray', $background);
	}

	public static function blue($string, $background = '') {
		return self::color($string, 'blue', $background);
	}

	public static function ltblue($string, $background = '') {
		return self::color($string, 'ltblue', $background);
	}

	public static function green($string, $background = '') {
		return self::color($string, 'green', $background);
	}

	public static function ltgreen($string, $background = '') {
		return self::color($string, 'ltgreen', $background);
	}

	public static function cyan($string, $background = '') {
		return self::color($string, 'cyan', $background);
	}

	public static function ltcyan($string, $background = '') {
		return self::color($string, 'ltcyan', $background);
	}

	public static function red($string, $background = '') {
		return self::color($string, 'red', $background);
	}

	public static function ltred($string, $background = '') {
		return self::color($string, 'ltred', $background);
	}

	public static function purple($string, $background = '') {
		return self::color($string, 'purple', $background);
	}

	public static function ltpurple($string, $background = '') {
		return self::color($string, 'ltpurple', $background);
	}

	public static function brown($string, $background = '') {
		return self::color($string, 'brown', $background);
	}

	public static function yellow($string, $background = '') {
		return self::color($string, 'yellow', $background);
	}

	public static function ltgray($string, $background = '') {
		return self::color($string, 'ltgray', $background);
	}

	public static function white($string, $background = '') {
		return self::color($string, 'white', $background);
	}

	public static function color($string, $foreground, $background = '') {
		$result = '';
		if(isset(self::$foregrounds[$foreground])) {
			$result .= "\033[" . self::$foregrounds[$foreground] . 'm';
		}
		if(isset(self::$backgrounds[$background])) {
			$result .= "\033[" . self::$backgrounds[$background] . 'm';
		}

		if($result == '') {
			$result = $string;
		}
		else {
			$result .= $string . "\033[0m";
		}

		return $result;
	}
}

?>