<?php
namespace noahasu\fishingRodPlugin\item;

use pocketmine\item\Durable;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\item\ItemUseResult;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemIdentifier;

use pocketmine\world\sound\ThrowSound;

use noahasu\fishingRodPlugin\FishingManager;
use noahasu\fishingRodPlugin\FishingTableFactory;
use noahasu\fishingRodPlugin\HookCustomData;
use noahasu\fishingRodPlugin\entity\{FishingHook,FishingLureHook};

use mt_rand;

class FishingRod extends Durable {
    const DAYTIME = 12000; //昼(0~12,000tick)
    const SUNSET = 13800; //夕方(12001~13,800tick)
    const NIGHT = 22200; //夜(13801~22,200tick)
    const MORNING = 24000; //朝(22201~24,000tick)

    

    public function __construct() {
        parent::__construct(new ItemIdentifier(ItemIds::FISHING_ROD, 0), 'fishing rod');
    }

    public function getMaxDurability():int {
        return 64;
    }

    /** アイテムを使うと初期状態に戻るため、$this -> fishingのようなものができない。*/
    public function onClickAir(Player $player, Vector3 $directionVector):ItemUseResult {
        $fm = FishingManager::getInstance();
        // is fishing now?
        if($fm -> isFishing($player)) {
            //End fishing.
            $hook = $fm -> getFishingHook($player);
            if($hook -> getCatchTime() > 0){
                $this -> sendItem($player);
                $this -> applyDamage(1);
            }
            if(!$hook -> isClosed()){
                $hook -> flagForDespawn();
            }
            $fm -> endFishing($player);
        } else {
            //Start fishing.
            $this -> hookType = $type = HookCustomData::getInstance() -> getHook($player);
            HookCustomData::getInstance() -> setHookStack($player);
            $hook;
            if($type == 'bait')
                $hook = new FishingHook($player -> getLocation(), $player, null);
            else if($type == 'lure')
                $hook = new FishingLureHook($player -> getLocation(), $player, null);
            else return ItemUseResult::NONE();
            $hook -> spawnToAll();
            $fm -> startFishing($player, $hook);
            $player -> getWorld() -> addSound($player -> getPosition(),new ThrowSound,[$player]);
        }
        return ItemUseResult::SUCCESS();
    }

    public function sendItem(Player $player) {
        $inv = $player -> getInventory();
        $world = $player -> getWorld();
        $time = $world -> getTime();
        $timeString = 'daytime';
        switch(true) {
            case ($time <= self::DAYTIME):
                $timeString = 'daytime';
            break;
            case ($time <= self::SUNSET):
                $timeString = 'sunset';
            break;
            case ($time <= self::NIGHT):
                $timeString = 'night';
            break;
            case ($time <= self::MORNING):
                $timeString = 'morning';
            break;
        }
        $pos = $player -> getPosition();
        $table = FishingTableFactory::getInstance() -> getFishingTable($world -> getProvider() -> getWorldData() -> getName());
        if($table == null) {
            $player -> sendTip('どうやらこのワールドではなにも釣れないそうだ…');
            return;
        }
        $item = $table -> getRandomItem($timeString,HookCustomData::getInstance() -> getHookStack($player));
        if($item != null && $inv -> canAddItem($item)) {
            $player -> sendMessage($item -> getName().'§rを釣り上げました！');
            $inv -> addItem($item);
        } else {
            $player -> sendTip('何も釣れなかった…');
        }
    }
}