<?php

namespace NGWeather\WeatherUnderground\API;

interface WeatherUndergroundOpenAPI
{
    public function getWeatherData( $features, $settings = null, $query, $format = 'json');
}
