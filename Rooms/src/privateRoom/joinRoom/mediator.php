<?php
namespace privateRoom\joinRoom;


use privateRoom\status;
use pocketmine\player\Player;

use pocketmine\block\utils\SignText;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class mediator {

    public function __construct($index){
        $this->index = $index;
    }

    public function mediatorKickALlPlayers(string $title){
        foreach (status::$nameRoomToPlayers as $playerName){
            $player = $this->index->getServer()->getPlayerExact($playerName);

            if (isset(status::$playerInRoom[$player->getName()])){
                unset(status::$playerInRoom[$player->getName()]);
                unset(status::$nameRoomToPlayers[$player->getName()]);
                status::$numberPlayersInRoom[$title] = status::$numberPlayersInRoom[$title] - 1;
    
    
                $player->teleport(new Position($this->index->rooms->get($title)["join"]["position"][0], $this->index->rooms->get($title)["join"]["position"][1], $this->index->rooms->get($title)["join"]["position"][2], $player->getWorld()));
    
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

    public function removeRoom(string $title){
        $this->index->rooms->remove($title);
        $this->index->software->remove($title);
        $value_array = $this->index->software->get("rooms");
        unset($value_array[$title]);
        $this->index->software->set("rooms", $value_array);

        $this->index->software->save();
        $this->index->rooms->save();
        
    }

}