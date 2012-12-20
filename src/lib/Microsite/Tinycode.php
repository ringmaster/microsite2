<?php

/**
 * Microsite - Tinycode
 *
 * @namespace Microsite
 * @php >= 5.4
 */
namespace Microsite;

/**
 * Tinycode produces non-obvious, non-repeating codes for integer values and decodes them
 */
class Tinycode
{

	private static $digits = [];
	public static $acceptable_chars = 'bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWZ0123456789';
	public static $length = 8;

	public static function init($base = 1, $length = 8) {
		self::$length = $length;
		srand($base);
		$chars = str_split(self::$acceptable_chars);
		for($z = 0; $z < self::$length; $z++) {
			shuffle($chars);
			self::$digits[$z] = $chars;
			shuffle(self::$digits[$z]);
		}
	}

	public static function to_code($integer) {
		$number = $integer;
		$index = 0;
		$digits = [];
		$digitsout = [];
		while($index < self::$length) {
			$mod = count(self::$digits[$index]);
			$digits[$index] = ($number + (($index > 0) ? $digits[$index-1] : 0)) % $mod;
			$digitsout[$index] = self::$digits[$index][ $digits[$index] ];
			$number = floor($number / $mod);
			$index++;
		}
		return implode('', $digitsout);
	}

	public static function to_int($code) {
		$number = 0;
		$digits = str_split($code);
		$ante = 1;
		$lastnum = 0;
		for($index = 0; $index < count($digits); $index++) {
			$mod = count(self::$digits[$index]);
			$num = array_search($digits[$index], self::$digits[$index]);
			$number = $number + (($mod + $num - $lastnum) % $mod) * $ante;
			$ante *= $mod;
			$lastnum = $num;
		}
		return $number;
	}

	public static function valid_code($value) {
		$valid = strlen($value) == self::$length;
		$digits = str_split($value);
		for($index = 0; $index < count($digits); $index++) {
			$valid = $valid && in_array($digits[$index], self::$digits[$index]);
			if(!$valid) return $valid;
		}
		return $valid;
	}

	public static function max_int() {
		$total = 1;
		foreach(self::$digits as $digits) {
			$total *= count($digits);
		}
		return $total;
	}

}