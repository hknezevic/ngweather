<?php

namespace NGWeather\WeatherUnderground;

interface URLHandler
{
    /**
     * Generates the URL by its identifier
     *
     * @param string $identifier
     * @param array $urlParameters
     * @param array $queryParameters
     *
     * @return string
     */
    public function generate( $identifier, $urlParameters = array(), $queryParameters = array() );

    /**
     * Parses the provided URL
     *
     * @param string $identifier
     * @param string $url
     *
     * @return array
     */
    public function parse( $identifier, $url );
}
