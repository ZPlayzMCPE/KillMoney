<?php

/*
 * KillMoney plugin for PocketMine-MP
 * Copyright (C) 2017 Kevin Andrews <https://github.com/kenygamer/KillMoney>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

namespace kenygamer\KillMoney;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{
    
    public function onEnable()
    {
        
        @mkdir($this->getDataFolder(), 0777, true);
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $cfg  = fopen($this->getDataFolder() . "config.yml", "a+");
            $data = file_get_contents("https://raw.githubusercontent.com/kenygamer/KillMoney/master/resources/config.yml");
            fwrite($cfg, $data);
            fclose($cfg);
            
            $cfg  = null;
            $data = null;
        }
        
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
        if ($this->config->get("enable.plugin") === false) {
            $this->getLogger()->info("§eDisabling plugin, enable.plugin is set to false.");
            $this->getPluginLoader()->disablePlugin($this);
            return true;
        } elseif ($this->config->get("enable.plugin") === true) {
            if (!is_dir($this->getServer()->getDataPath() . "/" . "plugins" . "/" . "EconomyAPI")) {
                $this->getLogger()->info("§eEconomyAPI dependency was not found, so the plugin could not be enabled.");
                $this->getPluginLoader()->disablePlugin($this);
                return true;
            } else {
                $this->getServer()->getPluginManager()->registerEvents($this, $this);
                $this->getLogger()->info("§aEnabling " . $this->getDescription()->getFullName() . "...");
                return true;
            }
        } else {
            $this->getLogger()->info(TF::RED . "Invalid value for enable.plugin, please choose true or false.");
            $this->getPluginLoader()->disablePlugin($this);
            return true;
        }
        
    }
    
    public function onDisable()
    {
        $this->getLogger()->info("§cDisabling " . $this->getDescription()->getFullName() . "...");
    }
    
    public function onDeath(PlayerDeathEvent $event)
    {
        $player  = $event->getPlayer();
        $playerN = $player->getName();
        if ($player->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
            if ($player->getLastDamageCause()->getDamager() instanceof Player) {
                $killer  = $player->getLastDamageCause()->getDamager();
                $killerN = $killer->getName();
                
                $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player->getName());
                if (!is_numeric($this->config->get("minimum.money"))) {
                    $this->getLogger()->info("§cNon-numeric value for minimum.money, please check that the config.yml is not corrupted.");
                }
                if ($money < $this->config->get("minimum.money") && $killer->hasPermission("killmoney.killer.receive.money")) {
                    $money_gain   = 0;
                    $money_lost   = 0;
                    $search       = array(
                        '{killer}',
                        '{victim}',
                        '{money_gain}',
                        '{money_lost}'
                    );
                    $replace      = array(
                        $killerN,
                        $playerN,
                        $money_gain,
                        $money_lost
                    );
                    $noreward_msg = str_replace($search, $replace, $this->config->get("noreward.message"));
                    $killer->sendMessage($noreward_msg);
                    $this->freeMemory();
                    return true;
                } else {
                    if (!is_numeric($this->config->get("killer.money"))) {
                        $this->getLogger()->info("§cNon-numeric value for killer.money, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    if (!is_numeric($this->config->get("victim.money"))) {
                        $this->getLogger()->info("§cNon-numeric value for minimum.money, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    $money_gain = $this->config->get("killer.money");
                    $money_lost = $this->config->get("victim.money");
                    if ($killer->hasPermission("killmoney.killer.receive.money")) {
                        $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->addMoney($killer->getName(), $money_gain);
                    }
                    if ($player->hasPermission("killmoney.victim.lose.money")) {
                        $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->reduceMoney($player->getName(), $money_lost);
                    }
                    
                    if (empty($this->config->get("killer.message"))) {
                        $this->getLogger()->info("§cUnexpected value for killer.message, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    if (empty($this->config->get("victim.message"))) {
                        $this->getLogger()->info("§cUnexpected value for victim.message, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    
                    $search     = array(
                        '{killer}',
                        '{victim}',
                        '{money_gain}',
                        '{money_lost}'
                    );
                    $replace    = array(
                        $killerN,
                        $playerN,
                        $money_gain,
                        $money_lost
                    );
                    $killer_msg = str_replace($search, $replace, $this->config->get("killer.message"));
                    $search     = array(
                        '{killer}',
                        '{victim}',
                        '{money_gain}',
                        '{money_lost}'
                    );
                    $replace    = array(
                        $killerN,
                        $playerN,
                        $money_gain,
                        $money_lost
                    );
                    $victim_msg = str_replace($search, $replace, $this->config->get("victim.message"));
                    if ($killer->hasPermission("killmoney.killer.receive.money")) {
                        $killer->sendMessage($killer_msg);
                    }
                    $player->sendMessage($victim_msg);
                    if ($player->hasPermission("killmoney.victim.lose.money")) {
                    }
                    $this->freeMemory();
                }
            }
        }
    }
    
    private function freeMemory()
    {
        /* A smart way to free some RAM */
        $player       = null;
        $playerN      = null;
        $killer       = null;
        $killerN      = null;
        $money        = null;
        $money_gain   = null;
        $money_lost   = null;
        $search       = null;
        $replace      = null;
        $noreward_msg = null;
        $killer_msg   = null;
        $victim_msg   = null;
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if (strtolower($command->getName()) === "killmoney") {
            if ($sender->hasPermission("killmoney.command")) {
                $fullname         = $this->getDescription()->getFullName();
                $killermoney      = $this->config->get("killer.money");
                $victimmoney      = $this->config->get("victim.money");
                $minimummoney     = $this->config->get("minimum.money");
                $api              = implode(",", $this->getDescription()->getCompatibleApis());
                $noreward_message = $this->config->get("noreward.message");
                $killer_message   = $this->config->get("killer.message");
                $victim_message   = $this->config->get("victim.message");
                $sender->sendMessage("§a---- §fShowing information of KillMoney §a----\n§6This server is running §a$fullname" . "\n§6Compatible API(s): §a" . $api . "\n§6Settings:-\n killer.money: §c$killermoney" . "$" . "\n§6 victim.money: §c$victimmoney" . "$" . "\n§6 minimum.money: §c$minimummoney" . "$" . "\n§6Messages:-\nnoreward.message: §c$noreward_message" . "\n§6killer.message: §c$killer_message" . "\n§6victim.message: §c$victim_message");
                $fullname         = null;
                $victimmoney      = null;
                $minimummoney     = null;
                $api              = null;
                $noreward_message = null;
                $killer_message   = null;
                $victim_message   = null;
                return true;
            } else {
                $fullname = $this->getDescription()->getFullName();
                $sender->sendMessage("§6This server is running §a$fullname" . "\n§bgithub.com/kenygamer/KillMoney");
                $fullname = null;
                return true;
            }
        }
        
    }
}
