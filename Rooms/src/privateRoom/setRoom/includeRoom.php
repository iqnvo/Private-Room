<?php

namespace privateRoom\setRoom;

use pocketmine\player\Player;
use privateRoom\setRoom\eventsRoom;
use privateRoom\main;
use pocketmine\event\player\PlayerInteractEvent;



class includeRoom{# ماله فايده

    public function __construct(main $index, Player $player, string $title, string $description, int $max){
        $this->index = $index;
        $this->title = $title;
        $this->description = $description;
        $this->max = $max;


        $this->setEvent($player);
    }

    private function setEvent(Player $player){
        new eventsRoom($player, $this->index, $this->title, $this->description, $this->max);
        return True;
    }


}