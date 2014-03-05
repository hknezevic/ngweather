<?php

use NGWeather\WeatherUnderground\REST\WeatherUndergroundOpenAPI;
use NGWeather\WeatherUnderground\URLHandler\DefaultHandler;
use NGWeather\WeatherUnderground\HTTPClient\SimpleHTTPClient;
use NGWeather\WeatherUnderground\OutputParser\JsonParser;
use NGWeather\WeatherUnderground\OutputParser\XMLParser;
use NGWeather\WeatherUnderground\FileManager\JsonFileManager;
use NGWeather\WeatherUnderground\FileManager\XMLFileManager;


class WeatherUndergroundAPI
{
    /**
     * Constructor
     */
    private function __construct() {}

    /**
     * Disallow cloning
     */
    private function __clone() {}

    /**
     * @var \NGWeather\WeatherUnderground\REST\WeatherUndergroundOpenAPI
     */
    protected static $weatherUndergroundApi;

    /**
     * Returns the WeatherUnderground API implementation
     *
     * @return\NGWeather\WeatherUnderground\REST\WeatherUndergroundOpenAPI
     */
    public static function getApi( )
    {
        if ( static::$weatherUndergroundApi === null )
        {
            $ini = eZINI::instance( 'ngweather.ini' );
            $sys = eZSys::instance();

            $storageDir = $sys::storageDirectory() . '/ngweather';

            static::$weatherUndergroundApi = new WeatherUndergroundOpenAPI(
                new DefaultHandler(
                    $ini->variable( 'WeatherUnderground', 'BaseURL' ),
                    $ini->variable( 'WeatherUnderground', 'APIKey' )
                ),
                new SimpleHTTPClient(),
                array(
                    'application/json' => new JsonParser(),
                    'application/xml' => new XMLParser()
                ),
                array(
                    'json' => new JsonFileManager( $storageDir ),
                    'xml' => new XMLFileManager( $storageDir )
                )
            );
        }

        return static::$weatherUndergroundApi;
    }
}