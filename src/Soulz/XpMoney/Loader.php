<?php

namespace Soulz\XpMoney;

use onebone\economyapi\EconomyAPI;

use libs\jojoe77777\FormAPI\CustomForm;
use libs\jojoe77777\FormAPI\SimpleForm;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase implements Listener {

    public const ALERT = TextFormat::DARK_GRAY . "[" . TextFormat::RED . "!" . TextFormat::DARK_GRAY . "]" . TextFormat::GRAY;

    public const SUCCESS = TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "!" . TextFormat::DARK_GRAY . "]" . TextFormat::GRAY;

    private function getScheduler(): TaskScheduler {
        return Loader::getInstance()->getScheduler();
    }

    public function sendForm(Player $player, $form): void {
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $form): void {
            $player->sendForm($form);
        }), 5);
    }

    public function onEnable(){
        $this->getServer()->getLogger(TextFormat::GRAY . "Server now Enabling XpMoney...");

        $this->getServer()->registerEvents($this, $this);
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool 
    {
        if($command->getName() == "xpmoney"){
            if(!$sender instanceof Player){
                $sender->sendMessage(Loader::ALERT . "You must execute this command in-game");
                return;
            }
            $exp = $sender->getCurrentTotalXp();
            $form = new SimpleForm(function(Player $player, $data): void {
                if($data !== null){
                    switch($data){
                        case "xpToMoney":
                            $this->sendXtmForm($player);
                            break;
                        case "moneyToXp":
                            $this->sendMtxForm($player);
                            break;
                }
            }
        });
            $eco = EconomyAPI::getInstance();
            $money = $eco->myMoney($player);

            // Base Simple Form
            $form->setTitle("Transfer Economies");
            $form->addButton(TextFormat::AQUA . "EXP => Money\n", -1, "", "xpToMoney");
            $form->addButton(TextFormat::AQUA . "Money => EXP\n", -1, "", "moneyToXp");
            $form->setContent(TextFormat::GRAY . "Choose How You'd Like to Transfer\n" . TextFormat::AQUA . "Your EXP: " . number_format($exp) . "\n" . TextFormat::AQUA . "Your Money: " . $money);
            $this->sendForm($sender, $form);
          
        }
    }

    // XP => Money Custom Form
    public function sendXtmForm(Player $player): void {
        $eco = EconomyAPI::getInstance();
        $form = new CustomForm(function(Player $player, $data): void {
            if($data !== null){
                $amount = intval($data["amount"]);
                $cost = $this->getConfig()->get("xp-per-money-tick") * $amount;
                if($player->getCurrentTotalXp() - $cost < 0) {
                    $player->sendMessage(Loader::ALERT . "You do not have enough EXP for this action! " . TextFormat::GOLD . "EXP Needed: " . $cost);
                } else {
                    $eco->addMoney($player, $amount);
                    $player->subtractXp($cost);
                    $player->sendMessage(Loader::SUCCESS . "You have bought "  . TextFormat::AQUA . "$ $amount " . TextFormat::GRAY . " for " . TextFormat::AQUA . $cost . TextFormat::GRAY . " XP!");
                }
                return;
            }
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::AQUA . "EXP => Money");
        $form->addLabel("§7EXP: $data §6Per: $1");
        $form->addSlider("Amount" , 1, 64, 1, 1, "amount");
        $this->sendForm($player, $form);
    }

    // Money => XP Custom Form
    public function sendMtxForm(Player $player): void {
        $eco = EconomyAPI::getInstance();
        $form = new CustomForm(function(Player $player, $data): void {
            if($data !== null){
                $amount = intval($data["amount"]);
                $cost = $this->getConfig()->get("money-per-xp-tick") * $amount;
                if($player->getMoney() - $cost < 0) {
                    $player->sendMessage(Loader::ALERT . "You do not have enough money for this action! " . TextFormat::GOLD . "Money Needed: " . $cost);
                } else {
                    $player->addtXp($amount);
                    $eco->reduceMoney($player, $amount)
                    $player->sendMessage(Loader::SUCCESS . "You have bought "  . TextFormat::AQUA . " $amount " . TextFormat::GRAY . " EXP for " . TextFormat::AQUA . $cost . TextFormat::GRAY . " Money!");
                }
                return;
            }
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::AQUA . "Money => Exp");
        $form->addLabel("§7Money: $data §6Per: 1 EXP");
        $form->addSlider("Amount" , 1, 64, 1, 1, "amount");
        $this->sendForm($player, $form);
    }
}
