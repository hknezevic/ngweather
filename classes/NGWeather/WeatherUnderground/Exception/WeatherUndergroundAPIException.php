<?php

namespace NGWeather\WeatherUnderground\Exception;

use \Exception;

class WeatherUndergroundAPIException extends Exception
{
    /**
     * @param string $type
     * @param string $description
     */
    public function __construct( $type, $description )
    {
        parent::__construct( "Request to WeatherUnderground API returned an error ( $type ). Description message: $description" );
    }
}
