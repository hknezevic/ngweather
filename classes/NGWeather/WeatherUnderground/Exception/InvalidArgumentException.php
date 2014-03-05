<?php

namespace NGWeather\WeatherUnderground\Exception;

use \Exception;

class InvalidArgumentException extends Exception
{
    /**
     * @param string $parameterName
     * @param string $parameterValue
     * @param string $source
     */
    public function __construct( $parameterName, $parameterValue, $source )
    {
        parent::__construct( "The parameter '$parameterName' has an invalid value '" . print_r( $parameterValue, true ) . "' in $source" );
    }
}
