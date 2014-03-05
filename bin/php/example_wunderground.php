<?php

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance(
    array(
        'description' => "",
        'use-session' => false,
        'use-modules' => true,
        'use-extensions' => true
    )
);

$script->startup();
$script->initialize();

$weatherUndergroundApi = WeatherUndergroundAPI::getApi();

try
{
    var_dump( $weatherUndergroundApi->getWeatherData( array('conditions'), null, 'Croatia/Zagreb', 'json' ));
    var_dump( $weatherUndergroundApi->getWeatherData( array('conditions', 'tide'), null, 'Croatia/Zagreb', 'xml' ));
}
catch (\Exception $e)
{
 var_dump( $e->getMessage());
}

$script->shutdown();
