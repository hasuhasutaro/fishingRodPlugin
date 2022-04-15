<?php
namespace noahasu\fishingRodPlugin;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\nbt\tag\CompoundTag;

use mt_rand;

class FishingTable {

    /** @var string $name is world name. */
    private string $name;
    private array $table;
    public function __construct(string $name, array $arr) {
        $this -> name = $name;
        $this -> table = $arr;
    }

    /** return world name */
    public function getName():string { return $this -> name;}

    public function getRandomType(string $time ,string $fishingType, ?string $upType, int $typeUpChance = 0):?string {
        if(!isset($this -> table[$time][$fishingType])) return null;
        $rand = mt_rand(1,1000);
        $types = $this -> table[$time][$fishingType]['typeChances'];
        if(isset($upType) && $typeUpChance > 0 && $types[$upType]) {
            if($upType == 'fish') {
                $baseChance = $types['fish'];
                $uped = round($baseChance *= ($typeUpChance + 1));
                $diff = $uped - $baseChance;
                $types['fish'] = $uped;
                $half = round($diff * 0.5);
                $types['junk'] -= $half;
                $types['treasure'] -= ($diff - $half);
            }
            if($upType == 'treasure') {
                $baseChance = $types['treasure'];
                $uped = round($baseChance *= ($typeUpChance + 1));
                $diff = $uped - $baseChance;
                $types['treasure'] = $uped;
                $types['junk'] -= $diff;
            }
        }
        $chanceSum = 0;
        foreach($types as $type => $chance) {
            $chanceSum += $chance;
            if($chanceSum < $rand) continue;
            return $type;
        }
        return null;
    }

    /**
     *  @var string $fishingType = bait or lure;
     *  @var int $typeUpChance = can UP select Fish|Treasure Chance. 10% = 0.1, 100% = 1, 200% = 2.
     */
    public function getRandomItem(string $time, string $fishingType, ?string $upType = null, int $typeUpChance = 0):?Item {
        $time = $this -> checkTime($time);
        $fishingType = $this -> checkFishingType($time, $fishingType);
        if($time == null || $fishingType == null) return null;
        $type = $this -> getRandomType($time, $fishingType, $upType, $typeUpChance);
        if($type == null) return null;
        $content = $this -> table[$time][$fishingType][$type];
        $rand = mt_rand(1,10000);
        foreach($content as $chance => $content2) {
            if($chance < $rand) continue;
            $item = clone $content2['item'];
            $lore = $item -> getLore();
            $priceAve = $content2['priceAve'];
            $price = 0;

            $nbt = $item -> getNamedTag() ?? new CompoundTag;
            if($type == 'fish') {
                $min = $content2['sizeMin'];
                $max = $content2['sizeMax'];
                $size = mt_rand($min,$max);
                $average = ($min+$max)/2;
                
                switch(true) {
                    case $size < $content2['big']:
                        $price = $priceAve * ($size/$average);
                    break;
                    case $size == $max:
                        $price = $priceAve * 5;
                        $lore[] = '§r★ §l§4MAX SIZE FISH';
                        $nbt -> setByte('maxSizeFish',1);
                    break;
                    case $size >= $content2['big']:
                        $price = $priceAve * ($size/$average);
                        $price *= 2;
                        $lore[] = '§r★ §l§eBIG FISH';
                        $nbt -> setByte('bigFish',1);
                    break;
                }
                $lore[] = '§rSize: '.$size * 0.1.'cm';
            }
            $price = round($price);

            $nbt -> setInt('price',$price);
            $item -> setNamedTag($nbt);

            $lore[] = 'Price: §e$'.$price;
            $item -> setLore($lore);
            return $item;
        }
    }

    private function checkFishingType(string $time, string $fishingType):?string {
        if(!isset($this -> table[$time][$fishingType])) {
            if($fishingType == 'bait') $fishingType = 'lure';
            else $fishingType = 'bait';
            if(!isset($this -> table[$time][$fishingType])) return null;
        }
        return $fishingType;
    }
    
    private function checkTime(string $time):?string {
        if(isset($this -> table[$time])) return $time;
        switch($time) {
            case 'daytime':
                if(isset($this -> table['night'])) return 'night';
                if(isset($this -> table['morning'])) return 'morning';
                if(isset($this -> table['sunset'])) return 'sunset';
            break;
            case 'night':
                if(isset($this -> table['daytime'])) return 'daytime';
                if(isset($this -> table['morning'])) return 'morning';
                if(isset($this -> table['sunset'])) return 'sunset';
            break;
            case 'morning':
                if(isset($this -> table['daytime'])) return 'daytime';
                if(isset($this -> table['night'])) return 'night';
                if(isset($this -> table['sunset'])) return 'sunset';
            break;
            case 'sunset':
                if(isset($this -> table['daytime'])) return 'daytime';
                if(isset($this -> table['night'])) return 'night';
                if(isset($this -> table['morning'])) return 'morning';
            break;
        }
        return null;
    }
}