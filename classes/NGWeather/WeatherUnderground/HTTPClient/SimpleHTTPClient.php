<?php

namespace NGWeather\WeatherUnderground\HTTPClient;

use NGWeather\WeatherUnderground\HTTPClient as HTTPClientInterface;


class SimpleHTTPClient implements HTTPClientInterface
{
    /**
     * @var $ch
     */
    protected $ch;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * Makes the request to provided URI
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     *
     * @return string
     */
    public function makeRequest( $url, $method = 'GET', $headers = null )
    {
        $ch = curl_init( $url );
        $result = null;

        $defaultHeaders = array(
            'Accept: application/json'
        );

        if ( is_array( $headers ) )
        {
            $defaultHeaders = $headers;
        }

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $defaultHeaders,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 10
        );

        curl_setopt_array( $ch, $options );

        try
        {
            $result = curl_exec($ch);
        }
        catch(\Exception $e)
        {
            // Do nothing
        }

        return $result;
    }
}
