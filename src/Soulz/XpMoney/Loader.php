<?php

namespace Soulz\XpMoney;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase implements Listener {

    /** @var self */
    private static $instance;

    /**
     * @return Loader
     */
    public static function getInstance(): self {
        return self::$instance;
    }

    public function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getResource("config.yml");

        $this-getServer()->getPluginManager()->registerEvents($this, $this);
    }
}
