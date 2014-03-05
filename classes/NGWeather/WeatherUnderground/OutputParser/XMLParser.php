<?php

namespace NGWeather\WeatherUnderground\OutputParser;

use NGWeather\WeatherUnderground\OutputParser as OutputParserInterface;
use NGWeather\WeatherUnderground\Exception\WeatherUndergroundRuntimeException;
use NGWeather\WeatherUnderground\Exception\WeatherUndergroundAPIException;

use XML2Array;

class XMLParser implements OutputParserInterface
{
    /**
     * Parses the XML output from WeatherUnderground API
     *
     * @throws \NGWeather\WeatherUnderground\Exception\WeatherUndergroundAPIException
     * @throws \NGWeather\WeatherUnderground\Exception\WeatherUndergroundRuntimeException
     *
     * @param string $data
     *
     * @return array
     */
    public function parse( $data )
    {
        $parsedData = XML2Array::createArray($data);

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

        /** XML response from WeatherUnderground has slightly different data structure from JSON response, so we need to
         * manipulate with the data to return data structure similar to the one from JSON output parser.
         */
        $parsedData['response']['response'] = array( 'features' => array() );

        if ( is_array( $parsedData['response']['features']['feature'] ) && !empty( $parsedData['response']['features']['feature'] ) )
        {
            foreach( $parsedData['response']['features']['feature'] as $feature )
                $parsedData['response']['response']['features'][$feature] = 1;
        }
        elseif( is_string( $parsedData['response']['features']['feature'] ) && strlen( $parsedData['response']['features']['feature'] ) > 0 )
        {
            $feature = $parsedData['response']['features']['feature'];
            $parsedData['response']['response']['features'][$feature] = 1;
        }
        else
        {
            throw new WeatherUndergroundRuntimeException( $data );
        }

        unset ($parsedData['response']['features']);

        return $parsedData['response'];
    }
}
