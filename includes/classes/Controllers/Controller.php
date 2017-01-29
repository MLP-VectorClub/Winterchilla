<?php

namespace App\Controllers;

use App\CoreUtils;
use App\RegExp;
use App\Users;

abstract class Controller {
	/** @var array */
	protected $url;
	/** @var string */
	public $do;
	function __construct(){
		Users::authenticate();
	}
}