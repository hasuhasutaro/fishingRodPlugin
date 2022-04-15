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

    private function __construct() {}

    public static function getInstance():FishingTableFactory {
        if(!isset(self::$instance)) self::$instance = new self;
        return self::$instance;
    }

    public function register(string $jsonPath) {
            $json = file_get_contents($jsonPath);
            $this -> makeTable(json_decode($json,true));
    }

    /** 
     * ワールド名からテーブルを取得する。
     * @return FishingTable|NULL
     */
    public function getFishingTable(string $worldName):?FishingTable {
        if(isset($this -> tables[$worldName])) {
            return $this -> tables[$worldName];
        }
        return isset($this -> tables['all']) ? $this -> tables['all'] : null;
    }

    /** 
     * jsonファイルから入力されたデータからテーブルクラスを作成する
     */
    public function makeTable(array $table) {
        $worldName = $table['world_name']; 
        $fishingTable = [];

        foreach($table['times'][0] as $time => $value) {
            //bait or lure
            foreach($value[0]['type'][0] as $fishingType => $value2) {
                $typeChanceSum = 0; //魚、宝、ゴミの確率合計を保存

                # fish,junk,treasure(key),確率(typeChance)
                foreach($value2[0] as $key => $typeChance) {
                    $typeChanceSum += $typeChance; //確率を足していく
                    $fishingTable[$time][$fishingType]['typeChances'][$key] = $typeChance;
                }

                /** ジャンルの確率合計が100%じゃなかったときに警告を出し、鯖を終了させる */
                if($typeChanceSum !== 1000) throw new Exception('Type chance sum is not 1000(100%) Table: '.$worldName.'FishngType: '.$fishingType);
                
                # jsonからアイテムを生成する。
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

                    /** 釣れるアイテムの確率合計が100%じゃなかったとき、鯖を終了させる */
                    if($chanceSum != 10000) throw new Exception('Chance sum is not 10000(100%). Table:'.$worldName.' FishingType: '.$fishingType.' Type:'.$type);
                    $fishingTable[$time][$fishingType][$type] = $data;
                }
            }
        }
        $this -> tables[$worldName] = new FishingTable($worldName, $fishingTable); //Tableクラスを生成し、保管しておく
    }
}