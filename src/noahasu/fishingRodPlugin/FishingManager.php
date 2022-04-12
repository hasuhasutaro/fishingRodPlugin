<?php
namespace noahasu\fishingRodPlugin;

use pocketmine\player\Player;

use noahasu\fishingRodPlugin\entity\Hooks;

class FishingManager {

    private static FishingManager $instance;
    private array $fishing = [];

    private function __construct() {}

    public static function getInstance():FishingManager {
        if(!isset(self::$instance)) self::$instance = new self;
        return self::$instance;
    }

    public function isFishing(Player $player) {
        return isset($this -> fishing[$player -> getName()]);
    }

    public function startFishing(Player $player, Hooks $hook):void {
        $this -> fishing[$player -> getName()] = $hook;
    }

    public function endFishing(Player $player):void {
        unset($this -> fishing[$player -> getName()]);
    }

    public function getFishingHook(Player $player):?Hooks {
        return $this -> fishing[$player -> getName()] ?? null;
    }
}