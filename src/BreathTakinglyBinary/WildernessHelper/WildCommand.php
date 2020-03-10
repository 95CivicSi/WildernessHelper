<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\WildernessHelper;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class WildCommand extends Command{

    public function __construct(){
        parent::__construct("wild", "Go to the wilderness world.", "/wild");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("This command must be used in game.");
        }
        $sender->teleport(WildernessHelper::getInstance()->getProvider()->getWildernessSpawn($sender));
    }

}