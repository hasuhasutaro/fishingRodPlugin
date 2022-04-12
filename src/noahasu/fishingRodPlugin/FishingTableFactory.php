<?php
namespace noahasu\fishingRodPlugin;

use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;

use noahasu\fishingRodPlugin\FishingTable;
use mt_rand;
use Exception;

class FishingTableFactory {

    private static FishingTableFactory $instance;
    private array $tables = [];
    private array $idToString = []; //[int biomeId => string name]

    private function __construct() {}

    public static function getInstance():FishingTableFactory {
        if(!isset(self::$instance)) self::$instance = new self;
        return self::$instance;
    }

    public function register(string $jsonPath) {
            $json = file_get_contents($jsonPath);
            $this -> makeTable(json_decode($json,true));
    }

    public function getFishingTableFromName(string $name):?FishingTable {
        if(isset($this -> tables[$name])) {
            return $this -> tables[$name];
        }
        return isset($this -> tables['all']) ? $this -> tables['all'] : null;
    }

    public function getFishingTableFromId(int $biomeId):?FishingTable {
        if(isset($this -> idToString[$biomeId])) return $this -> getFishingTableFromName($this -> idToString[$biomeId]);
        return isset($this -> tables['all']) ? $this -> tables['all'] : null;
    }

    public function getTableKeys():array {
        return array_keys($this -> tables);
    }

    public function makeTable(array $table) {
        $name = $table['name'];
        $biomeId = $table['biome_id'];
        $fishingTable = [];
        //var_dump($table);
        foreach($table['times'][0] as $time => $value) {
            foreach($value[0]['type'][0] as $fishingType => $value2) {
                $typeChanceSum = 0;
                // var_dump($value2[1]);
                foreach($value2[0] as $key => $typeChance) {
                    $typeChanceSum += $typeChance;
                    $fishingTable[$time][$fishingType]['typeChances'][$key] = $typeChance;
                }
                if($typeChanceSum !== 1000) throw new Exception('Type chance sum is not 1000(100%) Name: '.$name.'FishngType: '.$fishingType);
                foreach($value2[1] as $type => $content2) {
                    $data = [];
                    $chanceSum = 0;
                    foreach($content2 as $fish) {
                        $item = ItemFactory::getInstance() -> get($fish['id'],$fish['damage']);
                        if(isset($fish['name'])) $item -> setCustomName($fish['name']);
                        $lore = [];
                        if(isset($fish['rank'])) $lore[] = '§rRank: §l'.$fish['rank'].'§r';
                        $lore[] = '---------------';
                        if(isset($fish['lore'])) $lore = array_merge($lore, $fish['lore'],['---------------']);
                        $item -> setLore($lore);
                        $chanceSum += $fish['chance'];
                        $data[$chanceSum] = ['item' => $item, 'priceAve' => $fish['price_ave']];
                        if($type == 'fish') $data[$chanceSum] = array_merge($data[$chanceSum],['sizeMin' => $fish['size_min'], 'sizeMax' => $fish['size_max'], 'big' => $fish['big_border']]);
                    }
                    if($chanceSum != 10000) throw new Exception('Chance sum is not 10000(100%). Name:'.$name.' FishingType: '.$fishingType.' Type:'.$type);
                    $fishingTable[$time][$fishingType][$type] = $data;
                }
            }
        }
        $this -> tables[$name] = new FishingTable($name, $biomeId, $fishingTable);
        $this -> idToString[$biomeId] = $name;
    }
}