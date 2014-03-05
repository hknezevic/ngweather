<?php

namespace NGWeather\WeatherUnderground;

interface FileManager
{
    public function readFileContents( $fileName, $filePath );

    public function writeFileContents( $fileName, $filePath, $data );
}
