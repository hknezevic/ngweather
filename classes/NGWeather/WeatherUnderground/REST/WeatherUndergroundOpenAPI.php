<?php

namespace NGWeather\WeatherUnderground\REST;

use NGWeather\WeatherUnderground\API\WeatherUndergroundOpenAPI as WeatherUndergroundOpenAPIInterface;
use NGWeather\WeatherUnderground\Exception\InvalidArgumentValueException;
use NGWeather\WeatherUnderground\FileManager;
use NGWeather\WeatherUnderground\URLHandler;
use NGWeather\WeatherUnderground\HTTPClient;
use NGWeather\WeatherUnderground\OutputParser;
use NGWeather\WeatherUnderground\Exception\InvalidArgumentException;

class WeatherUndergroundOpenAPI implements WeatherUndergroundOpenAPIInterface
{
    /**
     * @var \NGWeather\WeatherUnderground\URLHandler
     */
    protected $urlHandler;

    /**
     * @var \NGWeather\WeatherUnderground\HTTPClient
     */
    protected $httpClient;

    /**
     * @var \NGWeather\WeatherUnderground\OutputParser[]
     */
    protected $outputParsers;

    /**
     * @var \NGWeather\WeatherUnderground\FileManager[]
     */
    protected $fileManagers;

    /**
     * @var array
     */
    protected $featureResponseMappingArray;

    /**
     * @var array
     */
    protected $featureDataTimeoutInterval;

    /**
     * Constructor
     *
     * @param \NGWeather\WeatherUnderground\URLHandler $urlHandler
     * @param \NGWeather\WeatherUnderground\HTTPClient $httpClient
     * @param \NGWeather\WeatherUnderground\OutputParser[] $outputParsers
     * @param \NGWeather\WeatherUnderground\FileManager[] $fileManagers;
     */
    public function __construct( URLHandler $urlHandler, HTTPClient $httpClient, $outputParsers, $fileManagers )
    {
        $this->urlHandler = $urlHandler;
        $this->httpClient = $httpClient;
        $this->outputParsers = $outputParsers;
        $this->fileManagers = $fileManagers;

        // define which response arrays present values for which feature
        $this->featureResponseMappingArray = array(
            'alerts'         => array( 'query_zone', 'alerts' ),
            'almanac'        => array( 'almanac' ),
            'astronomy'      => array( 'moon_phase', 'sun_phase' ),
            'conditions'     => array( 'current_observation' ),
            'forecast'       => array( 'forecast' ),
            'forecast10day'  => array( 'forecast' ),
            'geolookup'      => array( 'location' ),
            'hourly'         => array( 'hourly_forecast' ),
            'hourly10day'    => array( 'hourly_forecast' ) ,
            'rawtide'        => array( 'rawtide' ),
            'satellite'      => array( 'satellite' ),
            'tide'           => array( 'tide' ),
            'webcams'        => array( 'webcams' ),
            'yesterday'      => array( 'history' ) // history manager
        );

        // define data timeout interval for each feature dataset
        $this->featureDataTimeoutInterval = array(
            'alerts'         => 1800, // 30 minutes
            'almanac'        => 31536000, // a year
            'astronomy'      => 3600, // an hour
            'conditions'     => 3600,
            'forecast'       => 86400, // a day
            'forecast10day'  => 86400,
            'geolookup'      => 31536000, // a year
            'hourly'         => 86400,
            'hourly10day'    => 86400,
            'rawtide'        => 1800,
            'satellite'      => 900, // 15 minutes
            'tide'           => 1800,
            'webcams'        => 31536000, // a year
            'yesterday'      => 86400
        );
    }

    /**
     * Returns the weather data requested from WeatherUnderground
     *
     * @throws \NGWeather\WeatherUnderground\Exception\InvalidArgumentException If one of the arguments has an invalid value
     * @throws \NGWeather\WeatherUnderground\Exception\InvalidArgumentValueException If query doesn't match settings
     *
     * @param array $features
     * @param array $settings
     * @param string $query <p>
     * Supported query formats:
     * 1. country/city ( i.e. Croatia/Zagreb )
     * 2. US state/city ( i.e.  )
     * 3. US zipcode ( i.e. )
     * 4. pws ID ( i.e. pws: )
     * Not supported yet: latitude/longitude, autoip
     * </p>
     *
     * @param string $format <p>
     * Accepted values: 'json' or 'xml'
     * </p>
     *
     * @return mixed
     */
    public function getWeatherData( $features, $settings = null, $query, $format = 'json' )
    {
        if ( !is_array( $features ) )
        {
            throw new InvalidArgumentException( 'features', $features, __METHOD__ );
        }

        if ( !is_string( $query ) || ( is_string( $query ) && strlen( $query ) <= 0 ) )
        {
            throw new InvalidArgumentException( 'query', $query, __METHOD__ );
        }

        if( preg_match( '#^[a-zA-Z_]+/[a-zA-Z_]+$#i', $query ) )
        {
            $queryMode = 0;
        }
        elseif ( preg_match( '#^[a-zA-Z]{2}+/[a-zA-Z_]+$#', $query ) )
        {
            $queryMode = 1;
        }
        elseif( preg_match( '#^[0-9]+$#', $query ) )
        {
            $queryMode = 2;
        }
        elseif( preg_match( '#^pws:[0-9a-zA-Z]+$#', $query ) )
        {
            $queryMode = 3;
        }
        // TODO:
        //elseif( preg_match( '#^-?(0|[1-9]\d*)(\.\d+)?,-?(0|[1-9]\d*)(\.\d+)?$#', $query ) ){}
        else
        {
            throw new InvalidArgumentException( 'query', $query, __METHOD__ );
        }

        if ( $format !== 'json' && $format !== 'xml' )
        {
            throw new InvalidArgumentException( 'format', $format, __METHOD__ );
        }

        /* optional settings
         * lang - returns the API response in the specified language. Default: EN. For list of available language codes
         * see the following link: http://www.wunderground.com/weather/api/d/docs?d=language-support
         *
         * pws - use personal weather stations for conditions. Allowed values: 1 (true) or 0 (false) . Default: true
         *
         * bestfct - use Weather Underground Best Forecast for forecast. Allowed values: 1 (true) or 0 (false). Default: 1 (true)
         */
        $defaultSettings = array( 'lang' => 'EN',
                                  'pws' => 1,
                                  'bestfct' => 1);

        if( isset( $settings ) && is_array( $settings ) )
        {
            if ( !empty( $settings['lang'] ) && is_string( $settings['lang'] ) && strlen( $settings['lang'] ) > 0 )
                $defaultSettings['lang'] = $settings['lang'];
            if ( !empty( $settings['pws'] ) && is_bool( $settings['pws'] ) )
                $defaultSettings['pws'] =  (int) $settings['pws'];
            if ( !empty( $settings['bestfct'] ) && is_bool( $settings['bestfct'] ) )
                $defaultSettings['bestfct'] =  (int) $settings['bestfct'];
        }

        // generate file path string and name
        switch( $queryMode )
        {
            case 0:
                $filePath = $query;
                break;
            case 1:
                $filePath = '/USA/' . $query  ;
                break;
            case 2:
                $filePath = '/USA/ZIP/' . $query ;
                break;
            case 3:
                if ( $defaultSettings['pws'] == 0 )
                    throw new InvalidArgumentValueException( 'Invalid query: using search by pws ID, but pws is manually disabled in settings (pws = 0).' );
                $filePath = implode( '/', explode( ':', $query ) );
                break;
            default:
                $filePath = $query;
                break;
        }
        $fileName = $defaultSettings['lang'] .'.' . $format;

        $outputData = array();
        $upToDateFeatures = array();

        $savedData = $this->fileManagers[$format]->readFileContents( $fileName, $filePath );

        // if file content exists, check if it contains any existing up-to-date data, to avoid unnecessary API calls
        foreach( $savedData['features'] as $feature => $value )
        {
            if ( in_array( $feature , $features )
                && $savedData['lastUpdated'][$feature] >= ( time() - $this->featureDataTimeoutInterval[$feature] ) )
            {
                $upToDateFeatures[] = $feature;
                $outputData[$feature] = $savedData[$feature];
            }
        }

        $features = array_diff( $features, $upToDateFeatures );

        if( count( $features ) > 0 )
        {
            //format settings
            $formattedSettingsArray = array();
            foreach ( $defaultSettings as $key => $value )
                $formattedSettingsArray[] = (string)$key . ':' . (string)$value;
            $formattedSettings = implode( '/', $formattedSettingsArray );

            //format features
            $formattedFeatures = implode( '/', $features );

            $data = $this->httpClient->makeRequest(
                $this->urlHandler->generate(
                    'weather_data',
                    array(
                        'features' => $formattedFeatures,
                        'settings' => $formattedSettings,
                        'query' => $query,
                        'format' => $format
                    ),
                    array( )
                ),
                'GET',
                array(
                    'Accept: application/' . $format
                )
            );

            $parsedData = $this->outputParsers['application/' . $format]->parse( $data );

            foreach( $parsedData['response']['features'] as $feature => $value )
            {
                if ( $value == 1 )
                {
                    $outputData[$feature] = array();

                    foreach( $this->featureResponseMappingArray[$feature] as $responseDataArray )
                    {
                        //add data to output array
                        $outputData[$feature][$responseDataArray] = $parsedData[$responseDataArray];
                    }

                    //update file content array
                    if( empty( $savedData['features'] ) ) $savedData['features'] = array();
                    $savedData['features'][$feature] = 1;
                    if ( empty( $savedData['lastUpdated'] ) ) $savedData['lastUpdated'] = array();
                    $savedData['lastUpdated'][$feature] = time();

                    $savedData[$feature] = $outputData[$feature];
                }
            }

            // save data to file
            $this->fileManagers[$format]->writeFileContents( $fileName, $filePath, $savedData );
        }

        return $outputData;
    }
}
