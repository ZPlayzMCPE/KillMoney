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
    protected $config;
    
    public function onEnable()
    {
        
        @mkdir($this->getDataFolder(), 0777, true);
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $cfg  = fopen($this->getDataFolder() . "config.yml", "a+");
            $data = file_get_contents("https://raw.githubusercontent.com/kenygamer/KillMoney/master/resources/config.yml");
            fwrite($cfg, $data);
            fclose($cfg);
        }
        
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
        if ($this->config->get("enable.plugin") === false) {
            $this->getLogger()->info(TextFormat::YELLOW . "Disabling plugin, enable.plugin is set to false.");
            $this->getPluginLoader()->disablePlugin($this);
            return true;
        } elseif ($this->config->get("enable.plugin") === true) {
            if (!is_dir($this->getServer()->getDataPath() . "/" . "plugins" . "/" . "EconomyAPI")) {
                $this->getLogger()->info(TextFormat::YELLOW . "EconomyAPI dependency was not found, so the plugin could not be enabled.");
                $this->getPluginLoader()->disablePlugin($this);
                return true;
            } else {
                $this->getServer()->getPluginManager()->registerEvents($this, $this);
                $this->getLogger()->info(TextFormat::GREEN . "Enabling " . $this->getDescription()->getFullName() . "...");
                return true;
            }
        } else {
            $this->getLogger()->info(TextFormat::RED . "Invalid value for enable.plugin, please choose true or false.");
            $this->getPluginLoader()->disablePlugin($this);
            return true;
        }
        
    }
    
    public function onDisable()
    {
        $this->getLogger()->info(TextFormat::RED . "Disabling " . $this->getDescription()->getFullName() . "...");
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
                    $this->getLogger()->info(TextFormat::RED . "Non-numeric value for minimum.money, please check that the config.yml is not corrupted.");
                    return true;
                }
                if ($money < $this->config->get("minimum.money") && $killer->hasPermission("killmoney.killer.receive.money")) {
                    $money_gain = 0;
                    $money_lost = 0;
                    $search = array(
                        '{killer}',
                        '{victim}',
                        '{money_gain}',
                        '{money_lost}'
                    );
                    $replace = array(
                        $killerN,
                        $playerN,
                        $money_gain,
                        $money_lost
                    );
                    $noreward_msg = str_replace($search, $replace, $this->config->get("noreward.message"));
                    $killer->sendMessage($noreward_msg);
                    return true;
                } else {
                    if (!is_numeric($this->config->get("killer.money"))) {
                        $this->getLogger()->info(TextFormat::RED . "Non-numeric value for killer.money, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    if (!is_numeric($this->config->get("victim.money"))) {
                        $this->getLogger()->info(TextFormat::RED . "Non-numeric value for minimum.money, please check that the config.yml is not corrupted.");
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
                        $this->getLogger()->info(TextFormat::RED . "Unexpected value for killer.message, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    if (empty($this->config->get("victim.message"))) {
                        $this->getLogger()->info(TextFormat::RED . "Unexpected value for victim.message, please check that the config.yml is not corrupted.");
                        return true;
                    }
                    
                    $search = array(
                        '{killer}',
                        '{victim}',
                        '{money_gain}',
                        '{money_lost}'
                    );
                    $replace = array(
                        $killerN,
                        $playerN,
                        $money_gain,
                        $money_lost
                    );
                    $killer_msg = str_replace($search, $replace, $this->config->get("killer.message"));
                    $search = array(
                        '{killer}',
                        '{victim}',
                        '{money_gain}',
                        '{money_lost}'
                    );
                    $replace = array(
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
                        $player->sendMessage($victim_msg);
                    }
                }
            }
        }
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if (strtolower($command->getName()) == "killmoney") {
            if ($sender->hasPermission("killmoney.command")) {
               /* Config settings */
                $KillerMoney = $this->config->get("killer.money");
                $VictimMoney = $this->config->get("victim.money");
                $MinimumMoney = $this->config->get("minimum.money");
                $API = implode(", ", $this->getDescription()->getCompatibleApis());
                $noreward_message = $this->config->get("noreward.message");
                $killer_message = $this->config->get("killer.message");
                $victim_message = $this->config->get("victim.message");
               /* */
                $sender->sendMessage(TextFormat::GREEN . "---- " . TextFormat::WHITE . "Showing information of KillMoney" . TextFormat::GREEN . "----" . PHP_EOL . TextFormat::GOLD . "This server is running " . TextFormat::GREEN . $this->getDescription()->getFullName() . PHP_EOL . TextFormat::GOLD . "Compatible API(s): " . TextFormat::GREEN . $API . PHP_EOL . TextFormat::GOLD . "Settings:-" . PHP_EOL . "killer.money: " . TextFormat::RED . $KillerMoney" . "$" . PHP_EOL . TextFormat::GOLD . "victim.money: " . TextFormat::RED . $VictimMoney . "$" . PHP_EOL . TextFormat::GOLD . "minimum.money: " . TextFormat::RED . $MinimumMoney . "$" . PHP_EOL . TextFormat::GOLD . "Messages:-" . PHP_EOL . "noreward.message: " . TextFormat::RED . $noreward_message" . PHP_EOL . TextFormat::GOLD . "killer.message: " . TextFormat::RED . $killer_message" . PHP_EOL . TextFormat::GOLD . "victim.message: " . TextFormat::RED . $victim_message);
                return true;
            } else {
               /* It's one command, so not using prefixes */
                $sender->sendMessage(TextFormat::GOLD . "This server is running " . TextFormat::GREEN . $this->getDescription()->getFullName() . PHP_EOL . TextFormat::AQUA . "github.com/kenygamer/KillMoney");
                return true;
            }
        }
        
    }
}
