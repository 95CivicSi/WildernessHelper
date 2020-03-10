<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\WildernessHelper;


use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\MainLogger;

class SQLiteProvider{
    public const COLUMN_UUID = "uuid";
    public const COLUMN_SPAWN = "spawn";

    /** @var \SQLite3 */
    private $database;

    public function __construct(){
        $this->database = new \SQLite3(WildernessHelper::getInstance()->getDataFolder() . "playerSpawns.db");
        $this->database->exec("CREATE TABLE IF NOT EXISTS players (" . self::COLUMN_UUID . " TEXT PRIMARY KEY, " . self::COLUMN_SPAWN . " TEXT);");
    }

    public function getWildernessSpawn(Player $player) : Position{
        MainLogger::getLogger()->debug("Getting Wilderness Spawn for " . $player->getName());
        $uuid = $player->getXuid();
        $query = $this->database->query("SELECT spawn FROM players WHERE uuid='$uuid';");
        $resultArray = $query->fetchArray(SQLITE3_ASSOC);
        if(isset($resultArray[self::COLUMN_SPAWN])){
            MainLogger::getLogger()->debug("Found spawn string " . $resultArray[self::COLUMN_SPAWN]);
            $posArray = explode(":", $resultArray[self::COLUMN_SPAWN]);
            foreach($posArray as $posValue){
                MainLogger::getLogger()->debug("Value = $posValue");
            }
            if(!\count($posArray) === 3 or (!\is_numeric($posArray[0]) or !\is_numeric($posArray[2]) or !\is_numeric($posArray[2]))){
                MainLogger::getLogger()->error("Found incorrect value of " . $resultArray[self::COLUMN_SPAWN] . " in spawn column for player " . $player->getName());
            }else{
                MainLogger::getLogger()->debug("Returning saved position!");
                return new Position((int) $posArray[0], (int) $posArray[1], (int) $posArray[2], WildernessHelper::getInstance()->getWildernessLevel());
            }
        }
        MainLogger::getLogger()->info("Getting new spawn location...");
        return $this->getNewSpawnFor($player);
    }

    private function getNewSpawnFor(Player $player) : Position{
        $pos = WildernessHelper::getInstance()->getNewWildernessSpawn();
        $this->setWildernessSpawn($player, $pos);

        return $pos;
    }

    public function setWildernessSpawn(Player $player, Position $location){
        $posString = $location->x . ":" . $location->y . ":" . $location->z;
        $uuid = $player->getXuid();
        $stmt = $this->database->prepare("INSERT OR REPLACE INTO players (uuid, spawn) VALUES (:uuid, :spawn);");
        $stmt->bindValue(":uuid", $uuid);
        $stmt->bindValue(":spawn", $posString);
        $stmt->execute();
    }


}