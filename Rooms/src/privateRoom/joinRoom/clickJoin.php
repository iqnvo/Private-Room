<?php
namespace privateRoom\joinRoom;

use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerInteractEvent, PlayerChatEvent};

use pocketmine\player\Player;

use pocketmine\world\Position;

use privateRoom\status;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\math\Vector3;




class clickJoin implements Listener{

    public function __construct($index, Player $player){
        $this->index = $index;

        $this->rooms = $this->index->rooms;
        $this->software = $this->index->software;

        $this->index->getServer()->getPluginManager()->registerEvents($this, $index);

    }

    private function playerJoined(Player $player){ # if he joined = true
        if (isset(status::$playerInRoom[$player->getName()])){return true;}
    }

    private function join(Player $player, string $title){
        if ($this->playerJoined($player)){

            if ($this->index->setting->get("send_message_player_joined") == "on"){
                $player->sendTip($this->index->setting->get("message_player_joined"));

            }
            return False;
        }

        if (!isset($this->index->software->get($title)[0]) && !isset($this->index->software->get($title)[1]) && !isset($this->index->software->get($title)[2]) && !isset($this->index->software->get($title)[3])){
            return False;
        }



        if (status::$numberPlayersInRoom[$title] >= $this->rooms->get($title)["join"]["max"]){
            $message_max = $this->index->setting->get("message_max_room");
            $message_max = str_replace("{title}", $title, $message_max);
            $player->sendTip($message_max);
            return false;
        }

        $player->teleport(new Position($this->rooms->get($title)["leave"]["position"][0], $this->rooms->get($title)["leave"]["position"][1], $this->rooms->get($title)["leave"]["position"][2], $player->getWorld()));
        
        status::$playerInRoom = status::$playerInRoom + [$player->getName() => $title];
        status::$nameRoomToPlayers = status::$nameRoomToPlayers + [$player->getName() => $player->getName()];
        status::$numberPlayersInRoom[$title] = status::$numberPlayersInRoom[$title] + 1;




        $message_join = $this->index->setting->get("message_join_room");
        $message_join = str_replace("{title}", $title, $message_join);

        if ($this->index->setting->get("send_message_join_room") == "on"){
            $player->sendTIp($message_join);
        }

        $sign = $player->getWorld()->getBlock(new Position($this->index->rooms->get($title)["join"]["signPosition"][0], $this->index->rooms->get($title)["join"]["signPosition"][1], $this->index->rooms->get($title)["join"]["signPosition"][2], $player->getWorld()));


        $signText = $this->index->software->get($title)[0] . "\n" . $this->index->software->get($title)[1] . "\n" . $this->index->software->get($title)[2] . "\n" . $this->index->software->get($title)[3];

        $signText = str_replace("{p}", status::$numberPlayersInRoom[$title], $signText);
        $signText = str_replace("{m}", $this->index->rooms->get($title)["join"]["max"], $signText);
        $signText = SignText::fromBlob($signText);
        $sign->setText($signText);
        
        $player->getWorld()->setBlock(new Vector3($this->index->rooms->get($title)["join"]["signPosition"][0], $this->index->rooms->get($title)["join"]["signPosition"][1], $this->index->rooms->get($title)["join"]["signPosition"][2]), $sign);





        return True;
    }


    public function leave(Player $player, $title){
        if (isset(status::$playerInRoom[$player->getName()])){
            unset(status::$playerInRoom[$player->getName()]);
            unset(status::$nameRoomToPlayers[$player->getName()]);
            status::$numberPlayersInRoom[$title] = status::$numberPlayersInRoom[$title] - 1;


            $player->teleport(new Position($this->rooms->get($title)["join"]["position"][0], $this->rooms->get($title)["join"]["position"][1], $this->rooms->get($title)["join"]["position"][2], $player->getWorld()));

            $message_leave = $this->index->setting->get("message_leave_room");
            $message_leave = str_replace("{title}", $title, $message_leave);
            
            if ($this->index->setting->get("send_message_leave_room") == "on"){
                $player->sendTip($message_leave);

            }

            $sign = $player->getWorld()->getBlock(new Position($this->index->rooms->get($title)["join"]["signPosition"][0], $this->index->rooms->get($title)["join"]["signPosition"][1], $this->index->rooms->get($title)["join"]["signPosition"][2], $player->getWorld()));

            if (isset($this->index->software->get($title)[0])){
                $signText = $this->index->software->get($title)[0] . "\n" . $this->index->software->get($title)[1] . "\n" . $this->index->software->get($title)[2] . "\n" . $this->index->software->get($title)[3];
            }else{
                $this->index->inputSys("ERROR", $title  . " | ");
                
            }
    
            $signText = str_replace("{p}", status::$numberPlayersInRoom[$title], $signText);
            $signText = str_replace("{m}", $this->index->rooms->get($title)["join"]["max"], $signText);
            $signText = SignText::fromBlob($signText);
            $sign->setText($signText);
            
            $player->getWorld()->setBlock(new Vector3($this->index->rooms->get($title)["join"]["signPosition"][0], $this->index->rooms->get($title)["join"]["signPosition"][1], $this->index->rooms->get($title)["join"]["signPosition"][2]), $sign);
    
        }

        return True;
    }

    public function clickSign(PlayerInteractEvent $event){
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if (isset(status::$playersAdminMode[$player->getName()])){
            return False;
        }

        if (!$event->getBlock() instanceof BaseSign){
            return False;
        }


        if (isset(status::$player[$player->getName()])){
            return False;
        }


        if (status::$offRoom){
            $player->sendTip("close Room");
            return False;
        }
        $software = $this->software->get("rooms");

        if ($software == ""){
            return False;
        }

        foreach ($software as $rooms => $title){
            if ($block->getId() == $this->rooms->get($title)["join"]["id"]){
                $x = $block->getPosition()->getX();
                $y = $block->getPosition()->getY();
                $z = $block->getPosition()->getZ();

                $xS = $this->rooms->get($title)["join"]["signPosition"][0];
                $yS = $this->rooms->get($title)["join"]["signPosition"][1];
                $zS = $this->rooms->get($title)["join"]["signPosition"][2];

                if ($x === $xS && $y === $yS && $z === $zS){

                    if ($this->index->rooms->get($title)["leave"]["description"] == "{teleport}" or $this->index->rooms->get($title)["leave"]["description"] == "{Teleport}"){
                        $this->teleportSign($player, $title);


                        return true;
                    }

                    if (!isset(status::$playerInRoom[$player->getName()])){
                        $this->join($player, $title);

                    }
                    break;
                }
            }
        }
    }


    private function teleportSign(Player $player, string $title){
        $world = $this->rooms->get($title)["leave"]["world"];

        $this->index->getServer()->getWorldManager()->loadWorld($world);
        $player->Teleport(new Position($this->rooms->get($title)["leave"]["signPosition"][0], $this->rooms->get($title)["leave"]["signPosition"][1], $this->rooms->get($title)["leave"]["signPosition"][2], $this->index->getServer()->getWorldManager()->getWorldByName($world)));        
        
        return true;
    }

    public function clickSignForLeave(PlayerInteractEvent $event){
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if (isset(status::$playersAdminMode[$player->getName()])){
            return False;
        }

        if (!$event->getBlock() instanceof BaseSign){
            return False;
        }


        if (isset(status::$player[$player->getName()])){
            return False;
        }


        $software = $this->software->get("rooms");

        if ($software == ""){
            return False;
        }

        foreach ($software as $rooms => $title){
            if ($block->getId() == $this->rooms->get($title)["join"]["id"]){
                $x = $block->getPosition()->getX();
                $y = $block->getPosition()->getY();
                $z = $block->getPosition()->getZ();

                $xS = $this->rooms->get($title)["leave"]["signPosition"][0];
                $yS = $this->rooms->get($title)["leave"]["signPosition"][1];
                $zS = $this->rooms->get($title)["leave"]["signPosition"][2];

                if ($x === $xS && $y === $yS && $z === $zS){
                    $this->leave($player, $title);
                    break;
                }
            }
        }
    }

    
    public function chat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();

        $message_ready = $this->index->setting->get("message");
        $message_ready = str_replace("{player}", $player->getName(), $message_ready);
        $message_ready = str_replace("{message}", $message, $message_ready);


        if ($this->playerJoined($player)){
            if (isset(status::$nameRoomToPlayers[$player->getName()])){
                $event->cancel();

                foreach (status::$nameRoomToPlayers as $playerName){
                    $this->index->getServer()->getPlayerExact($playerName)->sendMessage($message_ready);
                }
            }
        }
    }

    public static function kickAllPlayers($title){
        foreach (status::$nameRoomToPlayers as $playerName){
            $player = $this->index->getServer()->getPlayerExact($playerName);

            if (isset(status::$playerInRoom[$player->getName()])){
                unset(status::$playerInRoom[$player->getName()]);
                unset(status::$nameRoomToPlayers[$player->getName()]);
                status::$numberPlayersInRoom[$title] = status::$numberPlayersInRoom[$title] - 1;
    
    
                $player->teleport(new Position($this->rooms->get($title)["join"]["position"][0], $this->rooms->get($title)["join"]["position"][1], $this->rooms->get($title)["join"]["position"][2], $player->getWorld()));
    
                $message_leave = $this->index->setting->get("message_leave_room");
                $message_leave = str_replace("{title}", $title, $message_leave);
                
                if ($this->index->setting->get("send_message_leave_room") == "on"){
                    $player->sendTip($message_leave);
    
                }
    
                $sign = $player->getWorld()->getBlock(new Position($this->index->rooms->get($title)["join"]["signPosition"][0], $this->index->rooms->get($title)["join"]["signPosition"][1], $this->index->rooms->get($title)["join"]["signPosition"][2], $player->getWorld()));
    
                $signText = $this->index->software->get($title)[0] . "\n" . $this->index->software->get($title)[1] . "\n" . $this->index->software->get($title)[2] . "\n" . $this->index->software->get($title)[3];
        
                $signText = str_replace("{p}", status::$numberPlayersInRoom[$title], $signText);
                $signText = str_replace("{m}", $this->index->rooms->get($title)["join"]["max"], $signText);
                $signText = SignText::fromBlob($signText);
                $sign->setText($signText);
                
                $player->getWorld()->setBlock(new Vector3($this->index->rooms->get($title)["join"]["signPosition"][0], $this->index->rooms->get($title)["join"]["signPosition"][1], $this->index->rooms->get($title)["join"]["signPosition"][2]), $sign);
        
    
                return True;
    
            }
            
        }

        
    }



    
}