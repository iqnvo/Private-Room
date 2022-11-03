<?php 

namespace privateRoom;


use privateRoom\setRoom\includeRoom;
use privateRoom\joinRoom\clickJoin;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as C, Config};

use pocketmine\player\Player;

use pocketmine\event\player\{PlayerJoinEvent, PlayerQuitEvent};

use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\block\utils\SignText;

use pocketmine\command\{Command, CommandSender};
use privateRoom\status;
use privateRoom\joinRoom\mediator;

class main extends PluginBase implements Listener{

    public function inputSys($title, $value, $colorTitle = C::YELLOW){
        $text = $colorTitle . "[$title] " . C::RESET . $value;
        return $text;
    }

    public function onEnable() : void{
        $this->getServer()->getLogger()->info($this->getServer()->getName() . $this->inputSys("PRIVATE ROOM ENABLE", ""));

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->rooms = new Config($this->getDataFolder()."rooms.json", Config::JSON);
        $this->software = new Config($this->getDataFolder()."software.json", Config::JSON);
        $this->setting = new Config($this->getDataFolder()."setting.json", Config::JSON);


        $this->setting();
        $this->statusValues();
    }

    private function setting(){
        if (!$this->setting->get("message")){
            $this->setting->set("message", "§7<{player}>§r -> §7{message}");
        }

        if (!$this->setting->get("message_player_joined")){
            $this->setting->set("message_player_joined", "ERROR THIS PLAYER ON READY JOINED");
        }

        if (!$this->setting->get("message_join_room")){
            $this->setting->set("message_join_room", "§aJoin " . "{title}");
        }

        if (!$this->setting->get("message_leave_room")){
            $this->setting->set("message_leave_room", "§cLeave " . "{title}");

        }

        if (!$this->setting->get("message_max_room")){
            $this->setting->set("message_max_room", "§cThe Room Is Full " . "{title}");
        }


        #send Message
        if ($this->setting->get("send_message_player_joined") == null){
            $this->setting->set("send_message_player_joined", "on");
        }

        if ($this->setting->get("send_message_join_room") == null){
            $this->setting->set("send_message_join_room", "on");
        }

        if ($this->setting->get("send_message_leave_room") == null){
            $this->setting->set("send_message_leave_room", "on");
        }

        if ($this->setting->get("send_message_max_room") == null){
            $this->setting->set("send_message_max_room", "on");
        }


        $this->setting->save();
    }

    private function statusValues(){
        if ($this->software->get("rooms") == null){
            return False;
        }

        foreach ($this->software->get("rooms") as $title){
            status::$numberPlayersInRoom[$title] = 0;
        }

        return true;

    }

    public function onCommand(CommandSender $sender, Command $cmd, String $Label, array $args) : bool {

        if (!$sender instanceof Player){
            $sender->sendMessage("This Command For Just Player");
            return true;
        }

        $command = $cmd->getName();

        switch ($command){

            case "setroom":
                if (!isset($args[0]) or !isset($args[1]) or !isset($args[2])){$sender->sendMessage($this->inputSys("PrivateRoom", "§c/setroom {title} {description} {max}")); return True;}

                if (isset(status::$useCommandSetRoom[$sender->getName()])){
                    $sender->sendMessage($this->inputSys("PrivateRoom", "§cYou are already in save mode"));
                    break;
                }

                $this->setIncludeRoom($sender, $args[0], $args[1], $args[2]);
                status::$useCommandSetRoom[$sender->getName()] = true;
            break;

            case "room":
                if (!isset($args[0])){
                    $sender->sendMessage($this->inputSys("PrivateRoom", "§c/room {on or off}"));

                    break;
                }

                if ($args[0] == "on"){
                    status::$offRoom = false;

                    $sender->sendMessage($this->inputSys("PrivateRoom", "§aon"));
                }

                if ($args[0] == "off"){
                    status::$offRoom = true;

                    $sender->sendMessage($this->inputSys("PrivateRoom", "§coff"));
                }
            break;

            case "listrooms":
                $sender->sendMessage("§e---------------------");
                $sender->sendMessage("§erooms:");
                foreach ($this->software->get("rooms") as $title){
                    $sender->sendMessage("§b- " . $title);
                }
            break;

            case "removeroom":
                if (!isset($args[0])){
                    $sender->sendMessage("set title room please");
                    break;
                }
                $m = new mediator($this);
                $m->mediatorKickALlPlayers($args[0]);
                $m->removeRoom($args[0]);
                

        }


        return True;
    }


    private function setIncludeRoom(Player $player, String $title, string $description, int $max){
        return new includeRoom($this, $player, $title, $description, $max);
    }

    public function joinPlayer(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if (isset(status::$playersOffline[$player->getName()])){
            $title = status::$playersOffline[$player->getName()];

            $player->teleport(new Position($this->rooms->get($title)["join"]["position"][0], $this->rooms->get($title)["join"]["position"][1], $this->rooms->get($title)["join"]["position"][2], $player->getWorld()));
            unset(status::$playersOffline[$player->getName()]);
        }

        new clickJoin($this, $player);
    }

    public function quitEvent(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        
        if (isset(status::$playerInRoom[$player->getName()])){
            $title = status::$playerInRoom[$player->getName()];
            status::$numberPlayersInRoom[$title] = status::$numberPlayersInRoom[$title] - 1;
            unset(status::$playerInRoom[$player->getName()]);
            unset(status::$nameRoomToPlayers[$player->getName()]);

            //$player->teleport(new Position($this->rooms->get($title)["join"]["position"][0], $this->rooms->get($title)["join"]["position"][1], $this->rooms->get($title)["join"]["position"][2], $player->getWorld()));
            status::$playersOffline[$player->getName()] = $title;

            $sign = $player->getWorld()->getBlock(new Position($this->rooms->get($title)["join"]["signPosition"][0], $this->rooms->get($title)["join"]["signPosition"][1], $this->rooms->get($title)["join"]["signPosition"][2], $player->getWorld()));

            $signText = $this->software->get($title)[0] . "\n" . $this->software->get($title)[1] . "\n" . $this->software->get($title)[2] . "\n" . $this->software->get($title)[3];
    
            $signText = str_replace("{p}", status::$numberPlayersInRoom[$title], $signText);
            $signText = str_replace("{m}", $this->rooms->get($title)["join"]["max"], $signText);
            $signText = SignText::fromBlob($signText);
            $sign->setText($signText);
            
            $player->getWorld()->setBlock(new Vector3($this->rooms->get($title)["join"]["signPosition"][0], $this->rooms->get($title)["join"]["signPosition"][1], $this->rooms->get($title)["join"]["signPosition"][2]), $sign);
    
        }
    }

}