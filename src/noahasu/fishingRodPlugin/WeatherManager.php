<?php
namespace noahasu\fishingRodPlugin;

class WeatherManager {
    private static WeatherManager $instance;

    private bool $rain = false;
    private bool $thunder = false;

    private function __construct() {}

    public static function getInstance(): WeatherManager {
        if(!isset(self::$instance)) self::$instance = new self;
        return self::$instance;
    }

    public function getWeather():string {
        if($this -> rain) return 'rain';
        if($this -> thunder) return 'thunder';
        return 'clear';
    }

    public function clear() {
        $this -> rain = false;
        $this -> thunder = false;
    }

    public function startRain() {
        $this -> rain = true;
    }

    public function stopRain() {
        $this -> rain = false;
    }

    public function startThunder() {
        $this -> thunder = true;
    }

    public function stopThunder() {
        $this -> thunder = false;
    }
}