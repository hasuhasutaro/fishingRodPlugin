<?php
namespace noahasu\fishingRodPlugin\entity;

use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

use pocketmine\player\Player;
use pocketmine\nbt\tag\CompoundTag;

use pocketmine\block\Water;

use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

use pocketmine\world\sound\PopSound;


use noahasu\fishingRodPlugin\FishingManager;
use noahasu\fishingRodPlugin\entity\Hooks;
use noahasu\fishingRodPlugin\item\FishingRod;

use mt_rand;

class FishingHook extends Hooks {

    protected $gravity = 0.1;
    private int $attractTime = 0;
    private int $catchTime = 0;
    private int $packetCount = 0;
    private bool $flag = false;

    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt, ?int $attractTime = null) {
        $location->y += 1.5;
        parent::__construct($location, $shootingEntity, $nbt);
        if(isset($shootingEntity))
            $this->setMotion($shootingEntity->getDirectionVector() -> multiply(1.3));
        $this -> setAttractTime($attractTime ?? (mt_rand(5,30) * 20));
    }

    public static function getNetworkTypeId():string {
        return EntityIds::FISHING_HOOK;
    }

    protected function entityBaseTick(int $tickdiff = 1):bool {
        if($this -> isClosed()) return false;
        $hasUpdate = parent::entityBaseTick($tickdiff);
        $owner = $this -> getOwningEntity();
        if(!$owner instanceof Player) {
            $this -> flagForDespawn();
            return true;
        }
        if(!$owner -> getInventory()-> getItemInHand() instanceof FishingRod || !$owner -> isAlive() || $owner -> isClosed() || $owner -> getLocation()-> getWorld() != $this -> getLocation() -> getWorld()) {
                $this -> flagForDespawn();
                return true;
        }
        $inWater = $this -> getWorld() -> getBlock($this -> getPosition()) instanceof Water;
        if($this -> flag) {
            $rand = mt_rand(0,1);
            $rand ? $this -> motion -> x = -(mt_rand(1,5) * 0.1) : $this -> motion -> x = (mt_rand(1,5) * 0.1);
            $rand = mt_rand(0,1);
            $rand ? $this -> motion -> z = -(mt_rand(1,5) * 0.1) : $this -> motion -> z = (mt_rand(1,5) * 0.1);
        }
        else if($this->isCollided && !$inWater) {
            $this->motion->x = 0;
			$this->motion->y = 0;
			$this->motion->z = 0;
			$hasUpdate = true;
        } else {
            $block = $this -> getWorld() -> getBlock($this -> getPosition());
            if($inWater) {
                $this -> motion -> x = $this -> motion -> x < 0.001 ? 0 : $this -> motion -> x *= 0.6;
                $this -> motion -> y = $this -> motion -> y < 0.001 ? 0 : $this -> motion -> y *= 0.5;
                $this -> motion -> z = $this -> motion -> z < 0.001 ? 0 : $this -> motion -> z*= 0.6;
                $this -> setMotion($this -> getMotion() -> multiply(0.5));
                if($this -> motion -> y < $this -> gravity) {
                    // 55/56
                    if(mt_rand(0,55) > 0) {
                        $randY = mt_rand(1,3) * 0.1 + 1;
                        $this -> motion -> y += ($this -> gravity * $randY);
                    }
                }
                // if(!$this -> waterFlag) {
                //     $this -> waterFlag = true;
                //     $pk = new ActorEventPacket;
                //     $pk-> actorRuntimeId = $this->getId();
                //     $pk-> eventId = ActorEvent::FISH_HOOK_POSITION;
                //     $owner -> getServer() -> broadcastPackets($this->getViewers(), [$pk]);
                // }
            }
            $hasUpdate = true;
        }

        if(!$inWater) {
            if($this -> catchTime > 0) --$this -> catchTime;
            else if($this -> flag) {
                $rand = mt_rand(0,6);
                $rand < 1 ? $owner -> sendTip('糸が切れてしまった…') : $owner -> sendTip('逃げられてしまった…');
                $this -> flagForDespawn();
            }
            return $hasUpdate;
        }

        if($this -> attractTime <= 0 && $this -> catchTime <= 0 && !$this -> flag/*&& mt_rand(0,100) < 19*/) {
            /** 浮きが下がってから逃げるまでの時間 */
            $this -> catchTime = mt_rand(1,2) * 20; //3~10s
            /** 魚がかかるまでの時間 */
            // $this -> attractTime = mt_rand(5,30) * 20; //5 ~ 30s
            // $this -> attract();
        } else if($this -> attractTime > 0) {
            --$this -> attractTime;
        }

        if($this -> catchTime > 0) {
            --$this -> catchTime;
            $owner -> sendTip('----§eH§4I§bT§r----');
            // if(!$this -> flag)
            if(!$this -> flag) {
                $this -> flag = true;
                $this -> sendXPSound();
                $this -> fishEatHook();
                // $this -> packetCount = 10;
            }
            $this -> motion -> y = -$this -> gravity * 0.1;
            // $this -> motion -> y -= 0.2;
        } else if($this -> flag){
            $rand = mt_rand(0,6);
            $rand < 1 ? $owner -> sendTip('糸が切れてしまった…') : $owner -> sendTip('逃げられてしまった…');
            $this -> flagForDespawn();
        }

        // if($this -> attractTime < 0 && $this -> catchTime < 0) {
        //     $this -> attractTime = 0;
        //     $this -> catchTime = 0;
        // }

        return $hasUpdate;
    }

    public function attract(){
        $owner = $this->getOwningEntity();
        if($owner instanceof Player){
            $pk = new ActorEventPacket();
            $pk-> actorRuntimeId = $this->getId();
            $pk-> eventId = ActorEvent::FISH_HOOK_BUBBLE;
            $owner -> getServer() -> broadcastPackets($this->getViewers(), [$pk]);
        }
    }

    public function fishEatHook() {
        $owner = $this->getOwningEntity();
		if($owner instanceof Player){
			$pk = new ActorEventPacket();
			$pk -> actorRuntimeId = $this->getId();
			$pk -> eventId = ActorEvent::FISH_HOOK_HOOK;
			$owner -> getServer() -> broadcastPackets($this->getViewers(), [$pk]);
		}
    }

    public function sendXPSound() {
        $owner = $this -> getOwningEntity();
        if($owner instanceof Player) $owner -> getNetworkSession() -> sendDataPacket(LevelSoundEventPacket::nonActorSound(LevelSoundEvent::NOTE, $owner -> getPosition(),false, (5 << 8 | 15)));
    }

    public function getCatchTime():int {
        return $this -> catchTime;
    }

    public function setAttractTime(int $time) {
        $this -> attractTime = $time;
    }

    public function flagForDespawn():void {
        $owner = $this -> getOwningEntity();
        parent::flagForDespawn();
        
        if(!$owner instanceof Player) return;
        FishingManager::getInstance() -> endFishing($owner);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.25, 0.25);
    }
}