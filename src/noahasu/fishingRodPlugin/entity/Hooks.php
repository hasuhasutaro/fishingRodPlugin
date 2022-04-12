<?php
namespace noahasu\fishingRodPlugin\entity;

use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\EntitySizeInfo;

class Hooks extends Projectile {
    public static function getNetworkTypeId():string {
        return 'minecraft:fishing_hook';
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.25, 0.25);
    }
}