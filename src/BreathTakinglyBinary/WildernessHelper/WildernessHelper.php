<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\WildernessHelper;


use FactionsPro\FactionMain;
use http\Exception\RuntimeException;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;

class WildernessHelper extends PluginBase{

    public const CONFIG_CENTER_X = "center_x";
    public const CONFIG_CENTER_Z = "center_z";
    public const CONFIG_RADIUS = "radius";
    public const CONFIG_RETRY_ATTEMPTS = "retry_attempts";
    public const CONFIG_USE_FACTIONS = "enable_FactionsPro_support";
    public const CONFIG_WILDERNESS_LEVEL_NAME = "wilderness_level";

    public const KEY_VARIANT_NAME = "wild";
    public const TAG_RECEIVED_TOKEN = "WHToken";


    /** @var WildernessHelper */
    private static $instance;


    /** @var int */
    private $centerX = 0;

    /** @var int  */
    private $centerZ = 0;

    /** @var FactionMain */
    private $factionsAPI;

    /** @var SQLiteProvider */
    private $provider;

    /** @var int */
    private $radius = 1000;

    private $retryAttempts = 50;

    /** @var bool */
    private $useFactions = false;

    /** @var string */
    private $wildernessLevelName = "world";

    public function onEnable(){
        self::$instance = $this;

        $this->wildernessLevelName = $this->getConfig()->get(self::CONFIG_WILDERNESS_LEVEL_NAME, "world");
        $this->getWildernessLevel();

        $x = $this->getConfig()->get(self::CONFIG_CENTER_X, 0);
        $z = $this->getConfig()->get(self::CONFIG_CENTER_Z, 0);
        if(\is_numeric($x) and \is_numeric($z)){
            $this->centerX = $x;
            $this->centerZ = $z;
        }else{
            $this->getLogger()->error("Center coordinates in config are invalid.  Setting Center to X; 0 Z: 0");
        }

        $radius = $this->getConfig()->get(self::CONFIG_RADIUS, 1000);
        if(\is_numeric($radius)){
            $this->radius = \abs($radius);
        }else{
            $this->getLogger()->error("Radius in config is invalid.  Setting to " . $this->radius);
        }

        $retries = $this->getConfig()->get(self::CONFIG_RETRY_ATTEMPTS, 50);
        if(\is_numeric($retries)){
            $this->retryAttempts = \abs($retries);
        }else{
            $this->getLogger()->error("Retry attempts in config are invalid.  Setting to " . $this->retryAttempts);
        }

        $this->getFactionsAPI();
        $this->provider = new SQLiteProvider();
        $this->getServer()->getCommandMap()->register("WildernessHelper", new WildCommand());
    }

    /**
     * @return WildernessHelper
     */
    public static function getInstance() : WildernessHelper{
        return self::$instance;
    }

    /**
     * @return bool
     */
    public function canUseFactions() : bool{
        return $this->useFactions;
    }

    /**
     * @return FactionMain|null
     */
    public function getFactionsAPI() : ?FactionMain{
        if(!$this->useFactions){
            return null;
        }
        if(!$this->factionsAPI instanceof FactionMain){
            $this->factionsAPI = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
            if(!$this->factionsAPI instanceof FactionMain){
                $this->useFactions = false;

                return null;
            }
        }

        return $this->factionsAPI;
    }

    /**
     * @return SQLiteProvider
     */
    public function getProvider() : SQLiteProvider{
        return $this->provider;
    }

    /**
     * @return Level
     * @throws LevelException
     */
    public function getWildernessLevel() : Level{
        $level = $this->getServer()->getLevelByName($this->wildernessLevelName);
        if($level === null){
            throw new LevelException("Required Level \"" . $this->wildernessLevelName . "\" not found.");
        }

        return $level;
    }

    /**
     * @return Position
     * @throws LevelException
     */
    public function getNewWildernessSpawn() : Position{
        $level = $this->getWildernessLevel();
        $empty = false;
        $attempts = 0;
        while(!$empty and $attempts < $this->retryAttempts){
            $attempts++;
            $x = \mt_rand(-$this->radius + $this->centerX, $this->radius + $this->centerX);
            $z = \mt_rand(-$this->radius + $this->centerZ, $this->radius + $this->centerZ);
            if(!$level->isChunkLoaded($x << 4, $z << 4)){
                if(!$this->useFactions or ($this->useFactions and !$this->factionsAPI->pointIsInPlot($x, $z, $this->wildernessLevelName))){
                    $empty = true;

                    return $level->getSafeSpawn(new Vector3($x, 70, $z));
                }
            }
        }
        throw new RuntimeException("Exceeded ". $this->retryAttempts ." attempts to get random location in " . $this->wildernessLevelName . "!");
    }
}