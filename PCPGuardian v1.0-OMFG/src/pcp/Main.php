<?php
namespace pcp;


use pocketmine\event\Listener;

use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\event\player\{
    PlayerDropItemEvent,
    PlayerJoinEvent,
    PlayerLoginEvent,
    PlayerItemHeldEvent,
    PlayerCommandPreprocessEvent,
    PlayerGameModeChangeEvent
};

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class Main extends PluginBase implements Listener
{
    function onLoad()
    {
        $this->saveResource('settings.yml');
        $this->setting = new Config($this->getDataFolder() . "settings.yml", CONFIG::YAML);
    }

    function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->notice('REVISED!');
    }

    function onGMChange(PlayerGameModeChangeEvent $event) : void 
    {
        if ($this->setting->get('gamemode-guard') && $event->getNewGamemode() <> 2) 
        {
            $this->clean( $event->getPlayer() );
        }
    }
    
    function onDrop(PlayerDropItemEvent $event) : void
    {
        /**switch( $event->getPlayer()->getGamemode() )
        {
            case 0: if ($this->setting->getNested('dropguard.survival')) $event->setCancelled(); break;
            case 1: if ($this->setting->getNested('dropguard.creative')) $event->setCancelled(); break;
            case 2: if ($this->setting->getNested('dropguard.adventure')) $event->setCancelled(); break;
        }**/

        if( ($event->getPlayer()->getGamemode() === 1) && ($this->setting->getNested('dropguard.creative')) )
        {
            $event->setCancelled();
        }
    }

    function onHeld(PlayerItemHeldEvent $event) : void
    {
        $player = $event->getPlayer();
        $world = strtolower($player->getLevel()->getFolderName());
        $item = $event->getItem();
        if (array_key_exists($world, $this->setting->getNested('banned')))
        {
            if (in_array($item->getId(), $this->setting->getNested("banned.$world.items") ))
            {
                $player->getInventory()->remove(Item::get($item->getId(), $item->getDamage(), $item->getCount() ));
                $player->addTitle("§c§lSorry", "§o§fThat item is banned in this world");
            }
        }
    }
    
    function onCommandPreprocess(PlayerCommandPreprocessEvent $event)
    {
        $player = $event->getPlayer();
        $world = strtolower($player->getLevel()->getFolderName());
        $command = explode(" ", $event->getMessage());
        if (array_key_exists($world, $this->setting->getNested('banned')))
        {
            if (in_array(strtolower($command[0]), $this->setting->getNested("banned.$world.commands") ))
            {
                $event->setCancelled();
                $player->addTitle("§c§l" . $command[0], "§o§fIs banned in this world");
                //$player->sendMessage("§7ERROR §c•>§l§f ".$command[0]." is unvailable in this world");
                return true;
            }
        }
    }
    
    function onDeath(EntityDamageEvent $event) : void
    {
        if($event->getEntity() instanceof Player)
        {
            if ($event->getCause() === 11 && $this->setting->get('void-guard'))
            {
                $event->setCancelled();
                $event->getEntity()->teleport( $this->getServer()->getDefaultLevel()->getSafeSpawn() );
            }
        }
    }

    function onLogin(PlayerLoginEvent $event) : void
    {
        if($event->getPlayer()->isOp())
        {
            $event->getPlayer()->setDisplayName($event->getPlayer()->getDisplayName()."§cᴼᴾ");
        }
    }

    function onJoin(PlayerJoinEvent $event) : void
    {
        if($event->getPlayer()->isOp())
        {
            $this->getServer()->broadcastMessage("§l§fAn §7[§cᴼᴾᴱᴿᴬᵀᴼᴿ§7]§f has joined the server");
        } 
    }
    
    private function clean(Player $player) : void 
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
    }
}   