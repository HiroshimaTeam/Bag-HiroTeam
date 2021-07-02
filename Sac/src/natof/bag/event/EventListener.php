<?php

namespace natof\bag\event;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\ InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use natof\bag\bag;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\Config;

class EventListener implements Listener
{
    public $inBag = false;

    public function onClick(PlayerInteractEvent $event)
    {
        $config = new Config(bag::getInstance()->getDataFolder() . "config.yml", 2);
        $item = $event->getItem();
        $player = $event->getPlayer();

        foreach ($config->get("add") as $name => $id) {
            $id = explode(":", $id);
            if ($item->getId() == $id[0]) {

                $menu = InvMenu::create(InvMenu::TYPE_HOPPER);
                $menu->setName("Bag");

                if ($item == $player->getInventory()->getItemInHand()) {
                    if ($item->getNamedTagEntry("bag")) {
                        $menu->getInventory()->setContents(unserialize($item->getNamedTag()->getString("bag")));

                        $menu->send($player);

                        $this->inBag = true;

                        $menu->setListener(function (InvMenuTransaction $transaction) use ($player, $item): InvMenuTransactionResult {
                            if ($item != $player->getInventory()->getItemInHand()) {
                                return $transaction->discard();
                            }
                            return $transaction->continue();
                        });

                        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($item): void {
                            if ($item == $player->getInventory()->getItemInHand()) {
                                self::saveBag($player, $item, $inventory);
                            }
                            $this->inBag = false;
                        });
                    } else {
                        $menu->send($player);
                        $this->inBag = true;

                        $menu->setListener(function (InvMenuTransaction $transaction) use ($player, $item): InvMenuTransactionResult {
                            if ($item != $player->getInventory()->getItemInHand()) {
                                return $transaction->discard();
                            }
                            return $transaction->continue();
                        });

                        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($item): void {
                            if ($item == $player->getInventory()->getItemInHand()) {
                                self::saveBag($player, $item, $inventory);
                            }
                            $this->inBag = false;
                        });


                        $player->getInventory()->setItemInHand($item);
                    }
                }
            }
        }
    }

    public function saveBag(Player $player, Item $item, Inventory $inventory)
    {

        $nbt = $item->getNamedTag() ?? new CompoundTag("", []);
        $nbt->setString("bag", serialize($inventory->getContents()));
        $nbt->setString("UUID", random_int(1, 100000));
        $item->setNamedTag($nbt);

        $player->getInventory()->setItemInHand($item);
    }

    public function Ontransaction(InventoryTransactionEvent $event)
    {
        if ($this->inBag == true) {
            $config = new Config(bag::getInstance()->getDataFolder() . "config.yml", 2);
            foreach ($config->get("add") as $name => $id) {
                $id = explode(":", $id);
                foreach ($event->getTransaction()->getActions() as $action) {
                    if ($action instanceof SlotChangeAction) {
                        $ItemPlayerClicked = $action->getSourceItem();
                        if ($ItemPlayerClicked->getId() == $id[0]) {
                            $event->setCancelled();
                            break;
                        }
                    }
                }
            }
        }
    }
}

