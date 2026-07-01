<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 19:51
 */

namespace UcikiDealsEngine\Core\Module;

use UcikiDealsEngine\Core\Module;
use UcikiDealsEngine\Core\WordPress\WordPressFunctions;
use UcikiDealsEngine\Core\WordPress\WordPressFunctionsInterface;

abstract class AbstractModule implements Module
{
	protected WordPressFunctions $wpFunctions;

	public function __construct(?WordPressFunctionsInterface $wpFunctions = null)
	{
		$this->wpFunctions = $wpFunctions instanceof WordPressFunctionsInterface
			? $wpFunctions
			: new WordPressFunctions($this);
		$this->wpFunctions->setClass($this);
	}
}
