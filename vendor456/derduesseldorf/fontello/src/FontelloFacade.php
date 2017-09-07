<?php namespace Derduesseldorf\Fontello;

use Illuminate\Support\Facades\Facade;

/**
 * Class FontelloFacade
 * @package Derduesseldorf\Fontello
 * @version 1.0.0.0
 * @author Derduesseldorf
 */
class FontelloFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor() {
        return 'fontello';
    }

}