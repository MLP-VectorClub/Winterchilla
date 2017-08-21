<?php

namespace App;
use App\Exceptions\JSONParseException;

/**
 * A custom JSON class wraper for native json_* functions
 * with defaults that make sense and parsing exceptions
 */
class JSON {
	const
		AS_OBJECT = false,
		PRETTY_PRINT = true;
	/**
	 * @param string $json
	 * @param bool   $assoc
	 * @param int    $depth
	 * @param int    $options
	 *
	 * @throws JSONParseException
	 *
	 * @return mixed
	 */
	public static function decode(string $json, bool $assoc = true, int $depth = 20, int $options = JSON_BIGINT_AS_STRING){
		$decoded = json_decode($json, $assoc, $depth, $options);
		if ($decoded === null && ($err = json_last_error()) !== JSON_ERROR_NONE)
			throw new JSONParseException(json_last_error_msg(), $err);
		return $decoded;
	}
	public static function encode($value, ?int $options = null, int $depth = 100){
		$opt = JSON_UNESCAPED_SLASHES;
		if ($options !== null)
			$opt |= $options;
		return json_encode($value, $opt, $depth);
	}
}
