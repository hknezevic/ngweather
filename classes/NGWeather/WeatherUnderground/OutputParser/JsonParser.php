<?php

namespace NGWeather\WeatherUnderground\OutputParser;

use NGWeather\WeatherUnderground\Exception\WeatherUndergroundAPIException;
use NGWeather\WeatherUnderground\Exception\WeatherUndergroundRuntimeException;
use NGWeather\WeatherUnderground\OutputParser as OutputParserInterface;


class JsonParser implements OutputParserInterface
{
    /**
     * Parses the JSON output from Weather Underground API
     *
     * @throws \NGWeather\WeatherUnderground\Exception\WeatherUndergroundRuntimeException
     * @throws \NGWeather\WeatherUnderground\Exception\WeatherUndergroundAPIException
     *
     * @param string $data
     *
     * @return array
     */
    public function parse( $data )
    {
        $parsedData = json_decode( $data, true );

        if ( !is_array( $parsedData ) )
        {
            throw new WeatherUndergroundRuntimeException( $data );
        }

        if ( empty( $parsedData['response'] ) )
        {
            throw new WeatherUndergroundRuntimeException( "Request returned empty response" );
        }

        if ( !empty( $parsedData['response']['error'] ) )
        {
            throw new WeatherUndergroundAPIException(
                !empty( $parsedData['response']['error']['type'] ) ? $parsedData['response']['error']['type'] : '',
                !empty( $parsedData['response']['error']['description'] ) ? $parsedData['response']['error']['description'] : ''
            );
        }

        return $parsedData;
    }
}
