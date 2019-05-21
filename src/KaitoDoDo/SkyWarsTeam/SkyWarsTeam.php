<?php
# plugin hecho por KaitoDoDo
namespace KaitoDoDo\SkyWarsTeam;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task as PluginTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use onebone\economyapi\EconomyAPI;
use KaitoDoDo\SkyWarsTeam\Resetmap;
use KaitoDoDo\SkyWarsTeam\RefreshArena;

class SkyWarsTeam extends PluginBase implements Listener {

        public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Sky" . TextFormat::GREEN . "Wars§6Team" . TextFormat::RESET . TextFormat::GRAY . "]";
	public $mode = 0;
	public $arenas = array();
	public $currentLevel = "";
        public $reds = [ ];
        public $blues = [ ];
        public $greens = [ ];
	
	public function onEnable()
	{
		  $this->getLogger()->info(TextFormat::AQUA . "Sky§aWars§6Team §bby OlimpoCraft");

                $this->getServer()->getPluginManager()->registerEvents($this ,$this);
                $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                if(!empty($this->economy))
                {
                $this->api = EconomyAPI::getInstance ();
                }
		@mkdir($this->getDataFolder());
                $config2 = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
		$config2->save();
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("arenas")!=null)
		{
			$this->arenas = $config->get("arenas");
		}
                foreach($this->arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		$items = array(array(1,0,30),array(1,0,20),array(3,0,15),array(3,0,25),array(4,0,35),array(4,0,15),array(260,0,5),array(261,0,1),array(262,0,5),array(267,0,1),array(268,0,1),array(272,0,1),array(276,0,1),array(283,0,1),array(297,0,3),array(298,0,1),array(299,0,1),array(300,0,1),array(301,0,1),array(303,0,1),array(304,0,1),array(310,0,1),array(313,0,1),array(314,0,1),array(315,0,1),array(316,0,1),array(317,0,1),array(320,0,4),array(354,0,1),array(364,0,4),array(366,0,5),array(391,0,5));
		if($config->get("chestitems")==null)
		{
			$config->set("chestitems",$items);
		}
		$config->save();
                
		$statistic = new Config($this->getDataFolder() . "/statistic.yml", Config::YAML);
		$statistic->save();
		$this->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 10);
        }
        
        public function getZip() {
        Return new RefreshArena($this);
        }
        
        public function onQuit(PlayerQuitEvent $event)
            {
                $player = $event->getPlayer();
                if (isset($this->reds[$player->getName()]))
                    {
			unset ($this->reds[$player->getName()]);
                    }
                if (isset($this->blues[$player->getName()]))
                    {
			unset ($this->blues[$player->getName()]);
                    }
                if (isset($this->greens[$player->getName()]))
                    {
			unset ($this->greens[$player->getName()]);
                    }
            }
	
	public function onDeath(PlayerDeathEvent $event){
        $jugador = $event->getEntity();
        $mapa = $jugador->getLevel()->getFolderName();
        if(in_array($mapa,$this->arenas))
		{
        if($event->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent)
        {
            $asassin = $event->getEntity()->getLastDamageCause()->getDamager();
        if($asassin instanceof Player)
        {
            $event->setDeathMessage("");
            foreach($jugador->getLevel()->getPlayers() as $pl)
                {
                $pl->sendMessage(TextFormat::RED . $jugador->getNameTag() . TextFormat::YELLOW . " was killed by " . TextFormat::GREEN . $asassin->getNameTag() . TextFormat::YELLOW . ".");
                }
        $statistic = new Config($this->getDataFolder() . "/statistic.yml", Config::YAML);
	$stats = $statistic->get($asassin->getName());
	$soFarPlayer = $stats[0];
	$soFarPlayer++;
	$stats[0] = $soFarPlayer;
	$statistic->set($asassin->getName(),$stats);
	$stat = $statistic->get($jugador->getName());
	$soFarPlay = $stat[1];
	$soFarPlay++;
	$stat[1] = $soFarPlay;
	$statistic->set($jugador->getName(),$stat);
	$statistic->save();
        }
                }
        $jugador->setNameTag($jugador->getName());
                if (isset($this->reds[$jugador->getName()]))
                    {
			unset ($this->reds[$jugador->getName()]);
                    }
                if (isset($this->blues[$jugador->getName()]))
                    {
			unset ($this->blues[$jugador->getName()]);
                    }
                if (isset($this->greens[$jugador->getName()]))
                    {
			unset ($this->greens[$jugador->getName()]);
                    }
        }
        }
        
        public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$sofar = $config->get($level . "StartTime");
			if($sofar > 0)
			{
				$to = clone $event->getFrom();
				$to->yaw = $event->getTo()->yaw;
				$to->pitch = $event->getTo()->pitch;
				$event->setTo($to);
			}
		}
	}
	
	public function onLogin(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
		$player->getInventory()->clearAll();
		$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
		$player->teleport($spawn,0,0);
	}
	
	public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$event->setCancelled(false);
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$event->setCancelled(false);
		}
	}
	
	public function onDamage(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			if($event->getEntity() instanceof Player && $event->getDamager() instanceof Player)
			{
					$level = $event->getEntity()->getLevel()->getFolderName();
					$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($level . "PlayTime") != null)
					{
						if($config->get($level . "PlayTime") > 750)
						{
							$event->setCancelled(true);
						}
                                                else if(isset($this->reds[$event->getEntity()->getName()]) && isset($this->reds[$event->getDamager()->getName()]))
                                                {
                                                    $event->setCancelled(true);
                                                }
                                                else if( isset($this->blues[$event->getEntity()->getName()]) && isset($this->blues[$event->getDamager()->getName()]))
                                                {
                                                    $event->setCancelled(true);
                                                }
                                                else if( isset($this->greens[$event->getEntity()->getName()]) && isset($this->greens[$event->getDamager()->getName()]))
                                                {
                                                    $event->setCancelled(true);
                                                }
					}
			}
		}
	}
	
	public function onCommand(CommandSender $player, Command $cmd, $label, array $args) : bool {
		$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
        switch($cmd->getName()){
			case "stm":
				if($player->isOp())
				{
					if(!empty($args[0]))
					{
						if($args[0]=="make")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->arenas,$args[1]);
									$this->currentLevel = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->prefix . "Touch the player spawn!");
									$player->setGamemode(1);
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
                                                                        $name = $args[1];
                                                                        $this->getZip()->zip($player, $name);
								}
								else
								{
									$player->sendMessage($this->prefix . "ERROR missing world.");
								}
							}
							else
							{
								$player->sendMessage($this->prefix . "ERROR missing parameters.");
							}
						}
						else
						{
							$player->sendMessage($this->prefix . "Invalid command.");
						}
					}
					else
					{
						$player->sendMessage($this->prefix . "/stm <make-leave> : Create Arena | Leave the game");
                                                $player->sendMessage($this->prefix . "/rankstm <Rank> <Player> : Set Rank(Ranks: Warrior, Warrior+, Archer, Pyromancer)");
                                                $player->sendMessage($this->prefix . "/stmstart : Start the game in 10 seconds");
					}
				}
				else
				{
                                    $player->sendMessage($this->prefix . "Oh no! You are not OP.");
				}
			return true;
                        
                        case "rankstm":
				if($player->isOp())
				{
				if(!empty($args[0]))
				{
					if(!empty($args[1]))
					{
					$rank = "";
					if($args[0]=="MasterDios+")
					{
						$rank = "§b[§l§cMaster§6Dios§a+§b]";
					}
					else if($args[0]=="Dios+")
					{
						$rank = "§b[§l§6Dios§a+§b]";
					}
					else if($args[0]=="Dios")
					{
						$rank = "§b[§6Dios§b]";
					}
					else
					{
						$rank = "§b[§a" . $args[0] . "§b]";
					}
					$config = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
					$config->set($args[1],$rank);
					$config->save();
					$player->sendMessage($args[1] . " got the rank: " . $rank);
					}
					else
					{
						$player->sendMessage("Missing parameter(s)");
					}
				}
				else
				{
					$player->sendMessage("Missing parameter(s)");
				}
				}
			return true;
                        
                        case "stmstart":
                            if($player->isOp())
				{
                                $player->sendMessage($this->prefix . "§aStarting in 10 seconds...");
                                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                $config->set("arenas",$this->arenas);
                                foreach($this->arenas as $arena)
                                {
                                        $config->set($arena . "PlayTime", 780);
                                        $config->set($arena . "StartTime", 10);
                                }
                                $config->save();
                                }
                                return true;
		}
	}
        
        public function onChat(PlayerChatEvent $event)
	{
		$player = $event->getPlayer();
		$message = $event->getMessage();
                $level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
		$config = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
		$rank = "";
		if($config->get($player->getName()) != null)
		{
			$rank = $config->get($player->getName());
		}
		$event->setFormat($rank . TextFormat::WHITE . $player->getName() . " §b:§f " . $message);
                }
	}
	
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		
		if($tile instanceof Sign) 
		{
			if($this->mode==26)
			{
				$tile->setText(TextFormat::AQUA . "[Join]",TextFormat::YELLOW  . "0 / 12","§f".$this->currentLevel,$this->prefix);
				$this->refreshArenas();
				$this->currentLevel = "";
				$this->mode = 0;
				$player->sendMessage($this->prefix . "Arena Registered!");
			}
			else
			{
				$text = $tile->getText();
				if($text[3] == $this->prefix)
				{
					if($text[0]==TextFormat::AQUA . "[Join]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
						$namemap = str_replace("§f", "", $text[2]);
						$level = $this->getServer()->getLevelByName($namemap);
                                                if($text[1]==TextFormat::YELLOW  . "0 / 12")
					{
                                                    $this->reds[$player->getName()] = $player;
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn1");
                                                $player->sendMessage($this->prefix . "You are §l§c[RED] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§c[RED] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "1 / 12")
					{
                                            $this->blues[$player->getName()] = $player;
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn2");
                                                $player->sendMessage($this->prefix . "You are §l§9[BLUE] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§9[BLUE] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "2 / 12")
					{
                                            $this->greens[$player->getName()] = $player;
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn3");
                                                $player->sendMessage($this->prefix . "You are §l§a[GREEN] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§a[GREEN] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "3 / 12")
					{
                                            $this->reds[$player->getName()] = $player;
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn4");
                                                $player->sendMessage($this->prefix . "You are §l§c[RED] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§c[RED] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "4 / 12")
					{
                                            $this->blues[$player->getName()] = $player;
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn5");
                                                $player->sendMessage($this->prefix . "You are §l§9[BLUE] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§9[BLUE] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "5 / 12")
					{
                                            $this->greens[$player->getName()] = $player;
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn6");
                                                $player->sendMessage($this->prefix . "You are §l§a[GREEN] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§a[GREEN] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "6 / 12")
					{
                                            $this->reds[$player->getName()] = $player;
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn7");
                                                $player->sendMessage($this->prefix . "You are §l§c[RED] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§c[RED] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "7 / 12")
					{
                                            $this->blues[$player->getName()] = $player;
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn8");
                                                $player->sendMessage($this->prefix . "You are §l§9[BLUE] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§9[BLUE] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "8 / 12")
					{
                                            $this->greens[$player->getName()] = $player;
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn9");
                                                $player->sendMessage($this->prefix . "You are §l§a[GREEN] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§a[GREEN] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "9 / 12")
					{
                                            $this->reds[$player->getName()] = $player;
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn10");
                                                $player->sendMessage($this->prefix . "You are §l§c[RED] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§c[RED] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "10 / 12")
					{
                                            $this->blues[$player->getName()] = $player;
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn11");
                                                $player->sendMessage($this->prefix . "You are §l§9[BLUE] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§9[BLUE] now");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "11 / 12")
					{
                                            $this->greens[$player->getName()] = $player;
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn12");
                                                $player->sendMessage($this->prefix . "You are §l§a[GREEN] now");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getName() . " is §l§a[GREEN] now");
                                                }
					}
						$spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$player->getInventory()->clearAll();
                                                $player->removeAllEffects();
                                                $player->setHealth(20);
                                                $config2 = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
						$rank = $config2->get($player->getName());
						if($rank == "§b[§l§cMaster§6Dios§a+§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::DIAMOND_AXE, 0, 1));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
						else if($rank == "§b[§l§6Dios§a+s§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::IRON_AXE, 0, 1));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
						else if($rank == "§b[§bSemidios§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::BOW, 0, 1));
                                                        $player->getInventory()->setItem(1, Item::get(Item::ARROW, 0, 10));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
						else if($rank == "§b[§6Dios§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::IRON_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::CHAIN_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::CHAIN_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::IRON_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::TNT, 0, 2));
                                                        $player->getInventory()->setItem(1, Item::get(Item::FLINT_AND_STEEL, 0, 1));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
					}
					else
					{
						$player->sendMessage($this->prefix . "You can't join");
					}
				}
			}
		}
		else if($this->mode>=1&&$this->mode<=12)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . "Spawn " . $this->mode . " has been registered!");
			$this->mode++;
			if($this->mode==13)
			{
				$player->sendMessage($this->prefix . "Tap anywhere to go back.");
			}
			$config->save();
		}
		else if($this->mode==13)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$level = $this->getServer()->getLevelByName($this->currentLevel);
			$level->setSpawn = (new Vector3($block->getX(),$block->getY()+2,$block->getZ()));
			$config->set("arenas",$this->arenas);
			$player->sendMessage($this->prefix . "Touch the sign to register Arena!");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);
			$config->save();
			$this->mode=26;
		}
	}
	
	public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 780);
			$config->set($arena . "StartTime", 90);
		}
		$config->save();
	}
}

class RefreshSigns extends PluginTask {
    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Sky" . TextFormat::GREEN . "Wars§6Team" . TextFormat::RESET . TextFormat::GRAY . "]";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->prefix)
				{
					$aop = 0;
                                        $namemap = str_replace("§f", "", $text[2]);
					foreach($allplayers as $player){if($player->getLevel()->getFolderName()==$namemap){$aop=$aop+1;}}
					$ingame = TextFormat::AQUA . "[Join]";
					$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($namemap . "PlayTime")!=780)
					{
						$ingame = TextFormat::DARK_PURPLE . "[Running]";
					}
					else if($aop>=12)
					{
						$ingame = TextFormat::GOLD . "[Full]";
					}
					$t->setText($ingame,TextFormat::YELLOW  . $aop . " / 12",$text[2],$this->prefix);
				}
			}
		}
	}
}

class GameSender extends PluginTask {
    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Sky" . TextFormat::GREEN . "Wars§6Team" . TextFormat::RESET . TextFormat::GRAY . "]";
    
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
	}
        
        public function getResetmap() {
        Return new Resetmap($this);
        }
  
	public function onRun($tick)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$arenas = $config->get("arenas");
		if(!empty($arenas))
		{
			foreach($arenas as $arena)
			{
				$time = $config->get($arena . "PlayTime");
				$timeToStart = $config->get($arena . "StartTime");
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
				if($levelArena instanceof Level)
				{
					$playersArena = $levelArena->getPlayers();
					if(count($playersArena)==0)
					{
						$config->set($arena . "PlayTime", 780);
						$config->set($arena . "StartTime", 90);
					}
					else
					{
						if(count($playersArena)>=2)
						{
							if($timeToStart>0)
							{
								$timeToStart--;
								foreach($playersArena as $pl)
								{
									$pl->sendTip("§e< " . TextFormat::GREEN . $timeToStart . " seconds to start§e >");
								}
                                                                if($timeToStart==89)
                                                                {
                                                                    $levelArena->setTime(7000);
                                                                        $levelArena->stopTime();
                                                                }
								if($timeToStart<=0)
								{
									$this->refillChests($levelArena);
								}
								$config->set($arena . "StartTime", $timeToStart);
							}
							else
							{
								$aop = count($levelArena->getPlayers());
                                                                $tages = array();
                                                                $colors = array();
								if($aop>=1)
								{
                                                                foreach($playersArena as $pl)
                                                                {
                                                                    $tags = $pl->getNameTag();
                                                                    array_push($tages, $tags);
                                                                }
                                                                    
                                                                    $nametags = implode("-", $tages);
                                                                    
									foreach($playersArena as $pl)
									{
                                                                            if((strpos($nametags, "§l§c[RED]") !== false) && (strpos($nametags, "§l§9[BLUE]") === false) && (strpos($nametags, "§l§a[GREEN]") === false))
                                                                                    {
										foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
										{
											$plpl->sendMessage($this->prefix . "§l§c[RED] §l§bTeam is the Winner in the map §a" . $arena);
										}
										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->setNameTag($pl->getName());
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                                                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
                                                                                $pl->setHealth(20);
                                                                                if(!empty($this->plugin->api))
                                                                                    {
                                                                                    $this->plugin->api->addMoney($pl,500);
                                                                                    }
                                                                                $this->getResetmap()->reload($levelArena);
                                                                                
									
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 180);
                                                                            }
                                                                            if((strpos($nametags, "§l§c[RED]") === false) && (strpos($nametags, "§l§9[BLUE]") !== false) && (strpos($nametags, "§l§a[GREEN]") === false))
                                                                                    {
										foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
										{
											$plpl->sendMessage($this->prefix . "§l§9[BLUE] §l§bTeam is the Winner in the map §a" . $arena);
										}
										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->setNameTag($pl->getName());
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                                                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
                                                                                $pl->setHealth(20);
                                                                                if(!empty($this->plugin->api))
                                                                                    {
                                                                                    $this->plugin->api->addMoney($pl,500);
                                                                                    }
                                                                                 $this->getResetmap()->reload($levelArena);
                                                                                
									
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 180);
                                                                            }
                                                                            if((strpos($nametags, "§l§c[RED]") === false) && (strpos($nametags, "§l§9[BLUE]") === false) && (strpos($nametags, "§l§a[GREEN]") !== false))
                                                                                    {
										foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
										{
											$plpl->sendMessage($this->prefix . "§l§a[GREEN] §l§bTeam is the Winner in the map §a" . $arena);
										}
										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->setNameTag($pl->getName());
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                                                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
                                                                                $pl->setHealth(20);
                                                                                if(!empty($this->plugin->api))
                                                                                    {
                                                                                    $this->plugin->api->addMoney($pl,500);
                                                                                    }
                                                                                $this->getResetmap()->reload($levelArena);
									
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 180);
                                                                            }
                                                                        }
								}
                                                                if(($aop>=2))
                                                                    {
                                                                    foreach($playersArena as $pl)
                                                                        {
                                                                        $nametag = $pl->getNameTag();
                                                                        array_push($colors, $nametag);
                                                                        }
                                                                        $names = implode("-", $colors);
                                                                        $reds = substr_count($names, "§l§c[RED]");
                                                                        $blues = substr_count($names, "§l§9[BLUE]");
                                                                        $greens = substr_count($names, "§l§a[GREEN]");
                                                                        foreach($playersArena as $pla)
                                                                        {
                                                                        $pla->sendTip("§l§cRED:" . $reds . "  §9BLUE:" . $blues . "  §aGREEN:" . $greens);
                                                                        }
                                                                }
								$time--;
								if($time == 779)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>--------------------------------");
                                                                                $pl->sendMessage("§e>§c¡Attention: §6The game is starting!");
                                                                                $pl->sendMessage("§e>§fUsing the map: §a" . $arena);
                                                                                $pl->sendMessage("§e>§bYou have §a15 §bseconds of Invencibility");
                                                                                $pl->sendMessage("§e>--------------------------------");
									}
								}
                                                                if($time == 765)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e--------------------------------");
                                                                                $pl->sendMessage("§e>§bYou have §a15 §bseconds of Invencibility");
                                                                                $pl->sendMessage("§e>§e--------------------------------");
									}
								}
								if($time == 750)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e-------------------");
                                                                                $pl->sendMessage("§e>§bYou are not Invincible");
                                                                                $pl->sendMessage("§e>§e-------------------");
									}
								}
                                                                if($time == 550)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e--------------------------");
                                                                                $pl->sendMessage("§e>§bPlugin remake by OlimpoCraft");
                                                                                $pl->sendMessage("§e>§e--------------------------");
									}
								}
                                                                if($time == 480)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e--------------------------");
                                                                                $pl->sendMessage("§e>§bThe chests have been refilled");
                                                                                $pl->sendMessage("§e>§e--------------------------");
									}
									$this->refillChests($levelArena);
								}
								if($time>=180)
								{
								$time2 = $time - 180;
								$minutes = $time2 / 60;
								}
								else
								{
									$minutes = $time / 60;
									if(is_int($minutes) && $minutes>0)
									{
										foreach($playersArena as $pl)
										{
											$pl->sendMessage($this->prefix . $minutes . " minutes remaining");
										}
									}
									else if($time == 30 || $time == 15 || $time == 10 || $time ==5 || $time ==4 || $time ==3 || $time ==2 || $time ==1)
									{
										foreach($playersArena as $pl)
										{
											$pl->sendMessage($this->prefix . $time . " seconds remaining");
										}
									}
									if($time <= 0)
									{
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
										$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										foreach($playersArena as $pl)
										{
											$pl->teleport($spawn,0,0);
											$pl->sendMessage($this->prefix . "No winners in the map §a" . $arena);
											$pl->getInventory()->clearAll();
                                                                                        $pl->setHealth(20);
                                                                                        $pl->setNameTag($pl->getName());
                                                                                        $this->getResetmap()->reload($levelArena);
										}
										$time = 780;
									}
								}
								$config->set($arena . "PlayTime", $time);
							}
						}
						else
						{
							if($timeToStart<=0)
							{
								foreach($playersArena as $pl)
								{
									foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
									{
										$plpl->sendMessage($this->prefix . $pl->getNameTag() . "§l§b Has Won in the map §a" . $arena);
									}
									$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
									$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
									$pl->getInventory()->clearAll();
									$pl->teleport($spawn,0,0);
                                                                        $pl->setHealth(20);
                                                                        $pl->setNameTag($pl->getName());
                                                                        if(!empty($this->plugin->api))
                                                                                    {
                                                                                    $this->plugin->api->addMoney($pl,500);
                                                                                    }
                                                                        $this->getResetmap()->reload($levelArena);
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 90);
							}
							else
							{
								foreach($playersArena as $pl)
								{
									$pl->sendTip(TextFormat::DARK_AQUA . "Need more players");
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 90);
							}
						}
					}
				}
			}
		}
		$config->save();
	}
	
	public function refillChests(Level $level)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Chest) 
			{
				$chest = $t;
				$chest->getInventory()->clearAll();
				if($chest->getInventory() instanceof ChestInventory)
				{
					for($i=0;$i<=26;$i++)
					{
						$rand = rand(1,3);
						if($rand==1)
						{
							$k = array_rand($config->get("chestitems"));
							$v = $config->get("chestitems")[$k];
							$chest->getInventory()->setItem($i, Item::get($v[0],$v[1],$v[2]));
						}
					}						//QXJyZWdsYWRvIHBvciBQb2NrZXRtaW5lU21hc2g=		
				}
			}
		}
	}
}
