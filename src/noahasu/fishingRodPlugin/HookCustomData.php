<?php
namespace noahasu\fishingRodPlugin;

use pocketmine\player\Player;

class HookCustomData {
    private static HookCustomData $instance;
    private array $hookData = [];
    private array $hookStack = [];

    public static function getInstance():HookCustomData {
        if(!isset(self::$instance)) self::$instance = new self;
        return self::$instance;
    }

    public function setHook(Player $player, string $type = 'bait') { $this -> hookData[$player -> getName()] = $type; }
    public function getHook(Player $player):string {
        if(!isset($this -> hookData[$player -> getName()])) return 'bait';
        return $this -> hookData[$player -> getName()];
    }

    public function setHookStack($player):string {
        $this -> hookStack[$player -> getName()] = $this -> getHook($player);
        return $this -> hookStack[$player -> getName()];
    }

    public function getHookStack($player):string {
        if(!isset($this -> hookStack[$player -> getName()])) return 'bait';
        return $this -> hookStack[$player -> getName()];
    }
}