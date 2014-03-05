<?php

namespace NGWeather\WeatherUnderground;

interface OutputParser
{
    public function parse( $data );
}
