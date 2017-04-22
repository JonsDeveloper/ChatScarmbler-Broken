<?php

namespace cscramble;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\Config;

class ScrambleTask extends \pocketmine\scheduler\PluginTask {
  
  public $pg;
  
  public function __construct(Main $m) {
    
    parent::__construct($m);
    
    $this->pg = $m;
    
  }
  
  public function onRun($t) {
    
    $key = array_rand($this->pg->rand->get("scramble-words"));
    $word = $this->pg->rand->get("scramble-words")[$key];
    
    $this->pg->win = $word;
    
    $price = mt_rand($this->pg->rand->get("min-price"), $this->pg->rand->get("max-price"));
    
    $this->pg->price = $price;
    
    $this->pg->getServer()->broadcastMessage("§b» Unscramble the word below by typing it in chat!\n\n» Word: ". str_shuffle($word) ."\n\n» First player who can unscramble it get $". $price ." in-game money!");
    $this->pg->getServer()->getScheduler()->scheduleDelayedTask(new ScrambleTask($this->pg), (20 * 60 * ($this->pg->rand->get("minutes-to-scramble"))));
  }
}

class Main extends PluginBase implements Listener {
  
  public $rand;
  
  public $win = null;
  
  public $price = null;
  
  public $eco;
  
  public function onEnable() {
    
    if(!is_dir($this->getDataFolder())) {
      
      mkdir($this->getDataFolder());
      
    }
    
    if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") == null) {
      
      $this->getServer()->getPluginManager()->disablePlugin($this);
	  return true;
    }
    
    $this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->getInstance();
    
    $this->rand = new Config($this->getDataFolder() ."setting.yml", Config::YAML, [
    "minutes-to-scramble" => 30,
    "min-price" => 1000,
    "max-price" => 5000,
    "scramble-words" => [
      "pumpkin",
      "herobrine",
      "minecraft",
      "player",
      "dragon",
      "pig"
    ]]);
    
    $this->getServer()->getScheduler()->scheduleDelayedTask(new ScrambleTask($this), (20 * 60 * ($this->rand->get("minutes-to-scramble"))));
    
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    
    }
    
   public function onChat(PlayerChatEvent $ev) {
     
     $msg = $ev->getMessage();
     $p = $ev->getPlayer();
     
     if($this->win !== null && $this->price !== null) {
       
       if($msg == $this->win) {
         
         $this->getServer()->broadcastMessage("§l§b". $p->getName() ."§a unscrambled the word: §e". $this->win ." §aAnd won $". $this->price);
         
         $this->eco->addMoney($p->getName(), $this->price);
         
         $this->win = null;
         $this->price = null;
         $ev->setCancelled(true);
       }
     }
   }
 }