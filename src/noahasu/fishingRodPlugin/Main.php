<?php
namespace noahasu\fishingRodPlugin;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;

use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;

use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

use pocketmine\network\mcpe\protocol\types\LevelEvent;

use pocketmine\item\ItemFactory;
use pocketmine\inventory\CreativeInventory;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;


use noahasu\fishingRodPlugin\item\{
    FishingRod
};


use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;

use noahasu\fishingRodPlugin\{FishingManager, WeatherManager};
use noahasu\fishingRodPlugin\FishingTableFactory;
use noahasu\fishingRodPlugin\HookCustomData;
use noahasu\fishingRodPlugin\entity\{
    FishingHook,
    FishingLureHook
};

class Main extends PluginBase implements Listener {
    public function onEnable():void {
        $this -> getServer() -> getPluginManager() -> registerEvents($this,$this);
        $this -> saveResource('tables/fishingTable.json');

        #plugin_data/.../tables/のjsonファイルを全て取得し、読み込む。
        $files = glob($this -> getDataFolder().'/tables/*.json');
        foreach($files as $path)
            FishingTableFactory::getInstance() -> register($path);
        $this -> loadFishing();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool {
        if(!$sender instanceof Player) return false;
        if($label != 'turi') return false;
        if(!isset($args[0]) || !($args[0] == 'lure' || $args[0] == 'bait')) {
            $sender -> sendMessage('釣りの種類(bait|lure)を入力してください。');
            return true;
        }
        HookCustomData::getInstance() -> setHook($sender,$args[0]);
        $sender -> sendMessage($args[0].'に変更しました！');
        return true;
    }

    public function sendPacket(DataPacketSendEvent $event) {
        $packets = $event -> getPackets();
        foreach($packets as $packet) {
            if(!$packet instanceof LevelEventPacket) continue;
            $eventId = $packet -> eventId;
            $wm = WeatherManager::getInstance();
            switch($eventId) {
                case LevelEvent::START_RAIN:
                    $wm -> startRain();
                break;
                case LevelEvent::STOP_RAIN:
                    $wm -> stopRain();
                break;
                case LevelEvent::START_THUNDER:
                    $wm -> startThunder();
                break;
                case LevelEvent::STOP_THUNDER:
                    $wm -> stopThunder();
                break;
            }
        }
    }

    private function loadFishing():void {
        # fishing rod を登録
        $item = new FishingRod;
        ItemFactory::getInstance() -> register($item,true);
        CreativeInventory::getInstance() -> add($item);

        # 浮き(entity)を登録
        EntityFactory::getInstance() -> register(
            FishingHook::class,
            function(World $world, CompoundTag $nbt):FishingHook {
                return new FishingHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
            }, ['FishingHook'], EntityLegacyIds::FISHING_HOOK
        );

        # ルアー(entity)を登録
        EntityFactory::getInstance() -> register(
            FishingLureHook::class,
            function(World $world, CompoundTag $nbt):FishingLureHook {
                return new FishingLureHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
            }, ['FishingLureHook'], EntityLegacyIds::FISHING_HOOK
        );
    }
}