<?php

namespace App;

use ActiveRecord\DateTime;
use Moment\Moment;

class Time {
	public const IN_SECONDS = [
		'year' =>   31557600,
		'month' =>  2592000,
		'week' =>   604800,
		'day' =>    86400,
		'hour' =>   3600,
		'minute' => 60,
		'second' => 1,
	];

	public const SHORT_UINTS = [
		'year' =>   'y',
		'month' =>  'mo',
		'week' =>   'w',
		'day' =>    'd',
		'hour' =>   'h',
		'minute' => 'm',
		'second' => 's',
	];

	/**
	 * Gets the difference between 2 timestamps
	 *
	 * @param int $now
	 * @param int $target
	 *
	 * @return array
	 */
	public static function difference(int $now, int $target):array {
		$nowdt = new \DateTime();
		$nowdt->setTimestamp($now);
		$targetdt = new \DateTime();
		$targetdt->setTimestamp($target);
		$diff = date_diff($nowdt, $targetdt, true);
		$subtract = $now - $target;

		return [
			'year' => $diff->y,
			'month' => $diff->m,
			'day' => $diff->d,
			'hour' => $diff->h,
			'minute' => $diff->i,
			'second' => $diff->s,
			'past' => $subtract > 0,
			'time' => abs($subtract),
			'target' => $target
		];
	}

	public static function differenceToString(array $diff, bool $short = false):string {
		$diff_text = '';
		foreach (array_keys(Time::IN_SECONDS) as $unit){
			if (empty($diff[$unit]))
				continue;
			if (!$short)
				$diff_text .= CoreUtils::makePlural($unit, $diff[$unit], PREPEND_NUMBER).' ';
			else $diff_text .= $diff[$unit].self::SHORT_UINTS[$unit].' ';
		}
		return rtrim($diff_text);
	}

	/**
	 * Converts $timestamp to an "X somthing ago" format
	 * Always uses the largest unit available
	 *
	 * @param int $timestamp
	 * @param int $now       For use in tests
	 *
	 * @return string
	 */
	private static function _from(int $timestamp, $now = null):string {
		Moment::setLocale('en_US');
		$out = new Moment(date('c',$timestamp));
		if (isset($now))
			$out = $out->from(new Moment(date('c', $now)));
		else $out = $out->fromNow();
		return $out->getRelative();
	}

	public const
		FORMAT_FULL = 'jS F Y, H:i:s T',
		FORMAT_READABLE = 'readable';
	/**
	 * Create an ISO timestamp from the input string
	 *
	 * @param int    $time
	 * @param string $format
	 * @param int    $now    For use in tests (with the readable format)
	 *
	 * @return string
	 */
	public static function format(int $time, string $format = 'c', $now = null):string {
		if ($format === self::FORMAT_READABLE)
			return self::_from($time, $now);

		$ts = gmdate($format, $time);
		if ($format === 'c')
			$ts = str_replace('+00:00','Z', $ts);
		if ($format !== 'c' && strpos($format, 'T') === false)
			$ts .= ' ('.date('T').')';
		return $ts;
	}

	public const
		TAG_EXTENDED = true,
		TAG_ALLOW_DYNTIME = 'yes',
		TAG_NO_DYNTIME = 'no',
		TAG_STATIC_DYNTIME = 'static';

	/**
	 * Create <time datetime></time> tag
	 *
	 * @param string|int|DateTime $timestamp
	 * @param bool                $extended
	 * @param string              $allowDyntime
	 * @param int                 $now           For use in tests
	 *
	 * @return string
	 */
	public static function tag($timestamp, bool $extended = false, string $allowDyntime = self::TAG_ALLOW_DYNTIME, ?int $now = null):string {
		if ($timestamp instanceof DateTime)
			$timestamp = $timestamp->getTimestamp();
		else if (\is_string($timestamp)){
			$ts = strtotime($timestamp);
			if ($ts === false)
				return '';
			$timestamp = $ts;
		}
		/** @var $timestamp int */

		$datetime = self::format($timestamp);
		$full = self::format($timestamp, self::FORMAT_FULL);
		$text = self::format($timestamp, self::FORMAT_READABLE, $now);

		switch ($allowDyntime){
			case self::TAG_NO_DYNTIME:
				$datetime .= "' class='nodt";
			break;
			case self::TAG_STATIC_DYNTIME:
				$datetime .= "' class='no-dynt-el";
			break;
		}

		return
			!$extended
			? "<time datetime='$datetime' title='$full'>$text</time>"
			: "<time datetime='$datetime'>$full</time>";
	}
}