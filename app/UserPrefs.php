<?php

namespace App;

use App\Models\User;
use App\Models\UserPref;
use RuntimeException;
use function array_key_exists;

class UserPrefs extends GlobalSettings {
  /** @see process */
  public const DEFAULTS = [
    'cg_itemsperpage' => 7,
    'cg_hidesynon' => 1,
    'cg_hideclrinfo' => 0,
    'cg_fulllstprev' => 1,
    'cg_nutshell' => 0,
    'p_vectorapp' => '',
    'p_hidediscord' => 0,
    'p_hidepcg' => 0,
    'p_homelastep' => 0,
    'cg_defaultguide' => null,
    'ep_noappprev' => 0,
    'ep_revstepbtn' => 0,
    'a_pcgearn' => 1,
    'a_pcgmake' => 1,
    'a_pcgsprite' => 1,
    'a_postreq' => 1,
    'a_postres' => 1,
    'a_reserve' => 1,
    'pcg_slots' => null,
  ];

  public const STRICT_COMPARE = [
    'pcg_slots' => true,
  ];

  /**
   * Gets a user preference item's value
   *
   * @param string $key
   * @param User   $for
   * @param bool   $disable_cache
   *
   * @return mixed
   */
  public static function get(string $key, ?User $for = null, bool $disable_cache = false) {
    $for ??= Auth::$user;

    $for_set = $for !== null && !empty($for->id);

    if ($for_set && !$disable_cache && isset(Users::$preferences_cache[$for->id][$key]))
      return Users::$preferences_cache[$for->id][$key];

    $default = null;
    if (isset(static::DEFAULTS[$key]))
      $default = static::DEFAULTS[$key];
    if (!$for_set && !Auth::$signed_in)
      return $default;

    $q = UserPref::findFor($key, $for);
    $value = $q->value ?? $default;
    if ($for_set)
      Users::$preferences_cache[$for->id][$key] = $value;

    return $value;
  }

  /**
   * Sets a preference item's value
   *
   * @param string $name
   * @param mixed  $value
   * @param User   $for
   *
   * @return bool
   */
  public static function set(string $name, $value, ?User $for = null):bool {
    if (empty($for)){
      if (!Auth::$signed_in)
        throw new RuntimeException("Empty \$for when setting user preference $name to".var_export($value, true));
      $for = Auth::$user;
    }

    if (!array_key_exists($name, static::DEFAULTS))
      Response::fail("Key $name is not allowed");
    $default = static::DEFAULTS[$name];

    if (strpos($name, "a_") === 0)
      Logs::logAction('staff_limits', [
        'setting' => $name,
        'allow' => $value,
        'user_id' => $for->id,
      ]);

    $strict = isset(self::STRICT_COMPARE[$name]);

    if (UserPref::has($name, $for)){
      /** @var UserPref $pref */
      $pref = UserPref::findFor($name, $for);
      unset(Users::$preferences_cache[$for->id][$name]);
      if ($strict ? $value === $default : $value == $default)
        return $pref->delete();
      else return $pref->update_attributes(['value' => $value]);
    }
    else if ($strict ? $value !== $default : $value != $default){
      unset(Users::$preferences_cache[$for->id][$name]);

      return (new UserPref([
        'user_id' => $for->id,
        'key' => $name,
        'value' => $value,
      ]))->save();
    }
    else return true;
  }

  public static function reset(string $key, ?User $for = null):bool {
    if (!array_key_exists($key, static::DEFAULTS))
      Response::fail("Key $key is not allowed");

    return self::set($key, static::DEFAULTS[$key], $for);
  }

  /**
   * Processes a preference item's new value
   *
   * @param string $name
   * @param mixed  $value
   *
   * @return mixed
   */
  public static function process(string $name, $value = null) {
    if ($value === null)
      $value = isset($_REQUEST['value']) ? CoreUtils::trim($_REQUEST['value']) : null;

    switch ($name){
      case 'cg_itemsperpage':
        $thing = 'Color Guide items per page';
        if (!is_numeric($value))
          throw new RuntimeException("$thing must be a number");
        $value = (int)$value;
        if ($value < 7 || $value > 20)
          throw new RuntimeException("$thing must be between 7 and 20");
      break;
      case 'p_vectorapp':
        if (!empty($value) && !isset(CoreUtils::VECTOR_APPS[$value]))
          throw new RuntimeException('The specified app is invalid');
      break;
      case 'cg_defaultguide':
        if (!empty($value)) {
          if (!isset(CGUtils::GUIDE_MAP[$value]))
            throw new RuntimeException('The specified default guide is invalid');
        }
        else $value = null;
      break;
      case 'p_hidediscord':
      case 'cg_hidesynon':
      case 'cg_hideclrinfo':
      case 'cg_fulllstprev':
      case 'cg_nutshell':
      case 'ep_revstepbtn':
        $value = $value ? 1 : 0;
      break;

      case 'a_pcgearn':
      case 'a_pcgmake':
      case 'a_pcgsprite':
      case 'a_postreq':
      case 'a_postres':
      case 'a_reserve':
        if (Permission::insufficient('staff'))
          Response::fail("You cannot change the $name preference");

        $value = $value ? 1 : 0;
      break;

      case 'pcg_slots':
        Response::fail("$name is an internal setting and cannot be modified by users");
    }

    return $value;
  }
}
