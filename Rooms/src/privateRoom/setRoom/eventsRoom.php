<?php
namespace privateRoom\setRoom;


use pocketmine\event\Listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;

use privateRoom\main;
use privateRoom\status;
use privateRoom\joinRoom\clickJoin;


use pocketmine\block\utils\SignText;
use pocketmine\block\BaseSign;

use pocketmine\event\player\PlayerQuitEvent;


use pocketmine\world\Position;
use pocketmine\math\Vector3;


class eventsRoom implements Listener{

    private $adminMode = False;

    private $joinSign = false;
    private $LeaveSign = false;

    private $dataSign = Null;
    


    public function __construct(Player $player, main $index, string $title, string $description, int $max){
        $this->index = $index;
        $this->title = $title;
        $this->description = $description;
        $this->max = $max;



        $this->index->getServer()->getPluginManager()->registerEvents($this, $index);
        $player->sendMessage($this->index->inputSys("PrivateRoom", "Break Sign Join"));

        $this->adminMode = True;
        $this->joinSign = True;
        $this->leaveSign = True;
    }

    public function quit(PlayerQuitEvent $event){
        if ($this->adminMode){
            $this->adminMode = False;
            $this->joinSign = False;
            $this->LeaveSign = False;
            $this->dataSign = null;
            
        }
    }
    public function breakBlock(BlockBreakEvent $event){
        $player = $event->getPlayer();
        if ($this->adminMode){

            status::$playersAdminMode[$player->getName()] = true;

            if ($event->getInstaBreak() && $event->getBlock() instanceof BaseSign){

                if ($this->joinSign){
                    $this->dataSign = ["join" => array("title" => $this->title, "world" => $player->getWorld()->getFolderName() ,"description" => $this->description, "id" => $event->getBlock()->getId(), "max" => $this->max, "signPosition" => [$event->getBlock()->getPosition()->getX(), $event->getBlock()->getPosition()->getY(), $event->getBlock()->getPosition()->getZ()], "position" => [$player->getPosition()->getX(), $player->getPosition()->getY(), $player->getPosition()->getZ()])];
                    
                    $this->joinSign = False;

                    $player->sendMessage($this->index->inputSys("PrivateRoom", "Break Sign Leave"));
                    #$this->resetSign($player, $event->getBlock(), new Vector3($event->getBlock()->getPosition()->getX(), $event->getBlock()->getPosition()->getY() + 1, $event->getBlock()->getPosition()->getZ()));
                    $event->cancel();
                    return True;
                }

                if ($this->leaveSign){
                    $this->dataSign = $this->dataSign + ["leave" => array("title" => $this->title, "world" => $player->getWorld()->getFolderName(), "description" => $this->description, "id" => $event->getBlock()->getId(), "max" => $this->max, "signPosition" => [$event->getBlock()->getPosition()->getX(), $event->getBlock()->getPosition()->getY(), $event->getBlock()->getPosition()->getZ()], "position" => [$player->getPosition()->getX(), $player->getPosition()->getY(), $player->getPosition()->getZ()])];

                    $this->leaveSign = False;

                }

                $this->adminMode = False;
                if ($this->description !== "{teleport}"){
                    $event->cancel();

                }

                $this->index->rooms->set($this->title, $this->dataSign);
                
                if ($this->index->software->get("rooms") == Null){
                    $this->index->software->set("rooms", [$this->title => $this->title]);
                }else{
                    $this->index->software->set("rooms", $this->index->software->get("rooms") + [$this->title => $this->title]);
                }


                #$this->resetSign($player, $event->getBlock(), new Vector3($event->getBlock()->getPosition()->getX(), $event->getBlock()->getPosition()->getY() + 1, $event->getBlock()->getPosition()->getZ()));
                $player->sendMessage($this->index->inputSys("PrivateRoom", "§aSaved Data !"));

                unset(status::$playersAdminMode[$player->getName()]);
                $this->dataSign = null;
                #############
                #############
                $this->index->rooms->save();
                $this->index->software->save();
                unset(status::$useCommandSetRoom[$player->getName()]);

                $sign = $player->getWorld()->getBlock(new Position($this->index->rooms->get($this->title)["join"]["signPosition"][0], $this->index->rooms->get($this->title)["join"]["signPosition"][1], $this->index->rooms->get($this->title)["join"]["signPosition"][2], $player->getWorld()));

                status::$numberPlayersInRoom[$this->title] = 0;

                if ($sign instanceof BaseSign){
                    $signText = $sign->getText()->getLines(); #array

                    $this->index->software->set($this->title, $signText);
                    $this->index->software->save();

                    $signText = $signText[0] . "\n" .  $signText[1] . "\n" . $signText[2] . "\n" . $signText[3];
                    $signText = str_replace("{p}", status::$numberPlayersInRoom[$this->title], $signText);
                    $signText = str_replace("{m}", $this->index->rooms->get($this->title)["join"]["max"], $signText);




                    $sign->setText(SignText::fromBlob($signText));

                    $player->getWorld()->setBlock(new Vector3($this->index->rooms->get($this->title)["join"]["signPosition"][0], $this->index->rooms->get($this->title)["join"]["signPosition"][1], $this->index->rooms->get($this->title)["join"]["signPosition"][2]), $sign);
                    
                    foreach (status::$nameRoomToPlayers as $playerName){
                        $player = $this->index->getServer()->getPlayerExact($playerName);


                        if (isset(status::$playerInRoom[$player->getName()])){
                            unset(status::$playerInRoom[$player->getName()]);
                            unset(status::$nameRoomToPlayers[$player->getName()]);                
                
                            $player->teleport(new Position($this->index->rooms->get($this->title)["join"]["position"][0], $this->index->rooms->get($this->title)["join"]["position"][1], $this->index->rooms->get($this->title)["join"]["position"][2], $player->getWorld()));
                
                            $message_leave = $this->index->setting->get("message_leave_room");
                            $message_leave = str_replace("{title}", $this->title, $message_leave);
                            
                            if ($this->index->setting->get("send_message_leave_room") == "on"){
                                $player->sendTip($message_leave);
                
                            }
                        }

                    }

                    
                }

        


            }

        }
        return True;
    }


}