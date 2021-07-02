<?php

namespace natof\bag;

use muqsit\invmenu\InvMenuHandler;
use natof\bag\event\EventListener;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;

class bag extends PluginBase{

    private static $instance;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->saveResource("config.yml");

        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
    }



    public function onLoad(){
        self::$instance = $this;
    }

    public static function getInstance(): bag{
        return self::$instance;
    }
}
