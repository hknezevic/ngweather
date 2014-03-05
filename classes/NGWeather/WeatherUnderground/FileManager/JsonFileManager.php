<?php

namespace NGWeather\WeatherUnderground\FileManager;

use NGWeather\WeatherUnderground\Exception\InvalidDataFormatException;
use NGWeather\WeatherUnderground\FileManager as FileManagerInterface;


class JsonFileManager implements FileManagerInterface
{
    /**
     * @var $storageDir
     */
    protected $storageDir;

    /**
     * Constructor
     *
     * @param string $storageDir
     */
    public function __construct( $storageDir )
    {
        $this->storageDir = $storageDir;
    }

    /** Reads contents
     *
     */
    public function readFileContents( $fileName, $filePath = null )
    {
        $fullFilePath = $this->storageDir . '/' . trim( $filePath, ' /' ) . '/' . $fileName;

        $file = file_get_contents( $fullFilePath );

        if ( !$file )
            return array();

        $fileContents = json_decode( $file, true );

        if ( !is_array( $fileContents ) )
        {
            throw new InvalidDataFormatException( 'Invalid data format (not a valid JSON file)' );
        }
        return $fileContents;
    }

    public function writeFileContents( $fileName, $filePath, $data )
    {
        if ( !( file_exists( $this->storageDir ) ) ) mkdir( $this->storageDir );

        $fullFilePath = $this->storageDir;

        if ( is_string( $filePath ) && strlen( trim( $filePath, ' /' ) ) > 0 )
        {
            $filePathArray = explode( '/', trim( $filePath, ' /' ) );
            foreach ( $filePathArray as $dir )
            {
                //create directory structure, if already doesn't exist
                $fullFilePath .= '/' . $dir;
                if ( !( file_exists( $fullFilePath ) ) ) mkdir( $fullFilePath );
            }
        }

        $fullFilePath .= '/' . $fileName;

        return file_put_contents( $fullFilePath, json_encode( $data ) );
    }
}