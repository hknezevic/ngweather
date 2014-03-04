<?php

namespace NGWeather\WeatherUnderground;

interface HTTPClient
{
    /**
     * Makes the request to provided URI
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     *
     * @return string
     */
    public function makeRequest( $url, $method = 'GET', $headers = null );
}
