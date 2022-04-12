<?php
namespace noahasu\fishingRodPlugin\item;

use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\item\ItemUseResult;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemIdentifier;

use noahasu\fishingRodPlugin\FishingManager;
use noahasu\fishingRodPlugin\entity\FishingZombieHook;

class SpecialFishingRod extends Item {

    public function __construct() {
        parent::__construct(new ItemIdentifier(ItemIds::FISHING_ROD, 1), '特製釣り竿');
    }

    /** アイテムを使うと初期状態に戻るため、$this -> fishingのようなものができない。*/
    public function onClickAir(Player $player, Vector3 $directionVector):ItemUseResult {
        $fm = FishingManager::getInstance();
        // is fishing now?
        if($fm -> isFishing($player)) {
            //End fishing.
            $hook = $fm -> getFishingHook($player);
            if(!$hook -> isClosed()){
                $hook -> flagForDespawn();
            }
            $fm -> endFishing($player);
        } else {
            //Start fishing.
            $hook = new FishingZombieHook($player -> getLocation(), $player, null);
            $hook -> spawnToAll();
            $fm -> startFishing($player, $hook);
            $player -> sendPopup('fishing!');
        }
        return ItemUseResult::SUCCESS();
    }
}