<?php

namespace NGWeather\WeatherUnderground\URLHandler;

use NGWeather\WeatherUnderground\URLHandler as URLHandlerInterface;
use NGWeather\WeatherUnderground\Exception\InvalidArgumentValueException;

class DefaultHandler implements URLHandlerInterface
{
    /**
     * @var string
     */
    protected $apiKey;

    protected $urls = array(
        'weather_data' => '/{features}/{settings}/q/{query}.{format}'
    );

    const PARAMETER_REGEX_PATTERN = '\{([a-zA-Z0-9+_]+)\}';

    /**
     * Constructor
     *
     * @param string $baseUrl
     * @param string $apiKey
     */
    public function __construct( $baseUrl, $apiKey )
    {
        $this->baseUrl = rtrim( $baseUrl, '/' );
        $this->apiKey = $apiKey;
    }

    /**
     * Generates the URL by its identifier
     *
     * @throws \NGWeather\WeatherUnderground\Exception\InvalidArgumentValueException if the provided identifier is not correct
     *
     * @param string $identifier
     * @param array $urlParameters
     * @param array $queryParameters
     *
     * @return string
     */
    public function generate( $identifier, $urlParameters = array(), $queryParameters = array() )
    {
        if ( empty( $this->urls[$identifier] ) )
        {
            throw new InvalidArgumentValueException( 'There is no URL with provided identifier' );
        }

        $url = $this->urls[$identifier];

        preg_match_all( '(' . self::PARAMETER_REGEX_PATTERN . ')', $url, $matches, PREG_SET_ORDER );

        foreach ( $matches as $match )
        {
            $url = str_replace( $match[0], $urlParameters[$match[1]], $url );
        }

        $validQueryParameters = array();
        $validQueryParametersString = '';
        if ( !empty( $queryParameters ) )
        {
            foreach ( $queryParameters as $parameter => $value )
            {
                if ( $value !== null )
                {
                    $validQueryParameters[] = $parameter . '=' . urlencode( $value );
                }
            }
        }

        if ( !empty( $validQueryParameters ) )
        {
            $validQueryParametersString = '?' . implode( '&', $validQueryParameters );
        }

        return $this->baseUrl . '/' . $this->apiKey . $url . $validQueryParametersString;
    }

    /**
     * Parses the provided URL
     *
     * @throws \NGWeather\WeatherUnderground\Exception\InvalidArgumentValueException If the provided identifier is not correct
     *
     * @param string $identifier
     * @param string $url
     *
     * @return array
     */
    public function parse( $identifier, $url )
    {
        if ( empty( $this->urls[$identifier] ) )
        {
            throw new InvalidArgumentValueException( 'There is no URL with provided identifier' );
        }

        if ( strpos( $url, $this->baseUrl ) !== 0 )
        {
            throw new InvalidArgumentValueException( 'The URL is not a valid WeatherUnderground URL' );
        }

        $url = str_replace( $this->baseUrl, '', $url );

        $pattern = $this->compile( $this->urls[$identifier] );
        if ( !preg_match( $pattern, $url, $match ) )
        {
            throw new InvalidArgumentValueException( 'URL did not match the pattern' );
        }

        foreach ( $match as $key => $value )
        {
            if ( is_numeric( $key ) )
            {
                unset( $match[$key] );
            }
        }

        return $match;
    }

    /**
     * Compiles the pattern to PCRE regular expression
     *
     * @throws \NGWeather\WeatherUnderground\Exception\InvalidArgumentValueException
     *
     * @param string $pattern
     *
     * @return string
     */
    protected function compile( $pattern )
    {
        $pcre = '(^';

        do
        {
            switch ( true )
            {
                case preg_match( '(^[^{]+)', $pattern, $match ):
                    $pattern = substr( $pattern, strlen( $match[0] ) );
                    $pcre .= preg_quote( $match[0] );
                    break;

                case preg_match( '(^' . self::PARAMETER_REGEX_PATTERN . ')', $pattern, $match ):
                    $pattern = substr( $pattern, strlen( $match[0] ) );
                    $pcre .= '(?P<' . $match[1] . '>[^/]+)';
                    break;

                default:
                    throw new InvalidArgumentValueException( 'Invalid pattern part: ' . $pattern );
            }
        }
        while ( $pattern );

        $pcre .= '$)S';

        return $pcre;
    }
}
