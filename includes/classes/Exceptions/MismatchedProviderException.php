<?php

namespace App\Exceptions;

class MismatchedProviderException extends \Exception {
	/** @var string|null */
	private $actualProvider;
	public function __construct($actualProvider){
		parent::__construct();
		$this->actualProvider = $actualProvider;
	}
	public function getActualProvider(){
		return $this->actualProvider;
	}
}
