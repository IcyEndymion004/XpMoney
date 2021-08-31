<?php

namespace Soulz\XpMoney;

use onebone\economyapi\EconomyAPI;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;


use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase implements Listener {

    public $plugin;

    public const ALERT = TextFormat::DARK_GRAY . "[" . TextFormat::RED . "!" . TextFormat::DARK_GRAY . "]" . TextFormat::GRAY;

    public const SUCCESS = TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "!" . TextFormat::DARK_GRAY . "]" . TextFormat::GRAY;


    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool 
    {
        if($command->getName() == "xpmoney"){
            if(!$sender instanceof Player){
                $sender->sendMessage(Loader::ALERT . "You must execute this command in-game");
            }
            $exp = $sender->getCurrentTotalXp();
            $form = new SimpleForm(function(Player $sender, $data): void {
                if($data !== null){
                    switch($data){
                        case "xpToMoney":
                            case "xpToMoney":
                                $this->sendXtmForm($sender);
                                break;
                            case "moneyToXp":
                                $this->sendMtxForm($sender);
                                break;
                }
            }
        });
            $economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->getInstance();
            $money = $economy->myMoney($sender);

            // Base Simple Form
            $form->setTitle("Transfer Economies");
            $form->addButton(TextFormat::AQUA . "EXP => Money\n", -1, "", "xpToMoney");
            $form->addButton(TextFormat::AQUA . "Money => EXP\n", -1, "", "moneyToXp");
            $form->setContent(TextFormat::GRAY . "Choose How You'd Like to Transfer\n" . TextFormat::AQUA . "Your EXP: " . number_format($exp) . "\n" . TextFormat::AQUA . "Your Money: " . $money);
            $sender->sendForm($form);
          
        }
    return true;
    }

    // XP => Money Custom Form
    public function sendXtmForm(Player $player): void {
        $form = new CustomForm(function(Player $player, $data): void {
            $economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->getInstance();
            if($data !== null){
                $amount = intval($data["amount"]);
                $cost = $this->getConfig()->get("xp-per-money-tick") * $amount;
                if($player->getCurrentTotalXp() - $cost < 0) {
                    $player->sendMessage(Loader::ALERT . "You do not have enough EXP for this action! " . TextFormat::GOLD . "EXP Needed: " . $cost);
                } else {
                    $economy->addMoney($player, $amount);
                    $player->subtractXp($cost);
                    $player->sendMessage(Loader::SUCCESS . "You have bought "  . TextFormat::AQUA . "$amount Exp" . TextFormat::GRAY . " for " . TextFormat::AQUA . $cost . TextFormat::GRAY . " XP!");
                }
                return;
            }
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::AQUA . "EXP => Money");
        $form->addLabel("§eEXP: 1 §6Per: $1");
        $form->addSlider("Amount" , 1, 64, 1, 1, "amount");
        $player->sendForm($form);
    }

    // Money => XP Custom Form
    public function sendMtxForm(Player $player): void {
        $form = new CustomForm(function(Player $player, $data): void {
            $economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->getInstance();
            if($data !== null){
                $amount = intval($data["amount"]);
                $cost = $this->getConfig()->get("money-per-xp-tick") * $amount;
                if($economy->myMoney($player) < 0) {
                    $player->sendMessage(Loader::ALERT . "You do not have enough money for this action! " . TextFormat::GOLD . "Money Needed: " . $cost);
                } else {
                    $player->addXp($amount);
                    $economy->reduceMoney($player, $amount);
                    $player->sendMessage(Loader::SUCCESS . "You have bought " . TextFormat::AQUA . " $amount " . TextFormat::GRAY . " EXP for " . TextFormat::AQUA . $cost . TextFormat::GRAY . " Money!");
                }
                return;
            }
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::AQUA . "Money => Exp");
        $form->addLabel("§6Money: 1$ §ePer: 1 EXP");
        $form->addSlider("Amount" , 1, 64, 1, 1, "amount");
        $player->sendForm($form);
    }
}
