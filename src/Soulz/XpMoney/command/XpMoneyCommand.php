<?php

namespace Soulz\XpMoney\command;

use onebone\economyapi\EconomyAPI;

use libs\jojoe77777\FormAPI\CustomForm;
use libs\jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\TextFormat;

class XpMoneyCommand extends Command implements PluginIdentifableCommand {

    private function getScheduler(): TaskScheduler {
        return Loader::getInstance()->getScheduler();
    }

    public function sendForm(Player $player, $form): void {
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $form): void {
            $player->sendForm($form);
        }), 5);
    }

    public function __construct(){
        parent::__construct("xpmoney", "Transfer Between XP and Money", "/xpmoney [money, xp]", ['moneyxp']);
        $this->setPermission("xpmoney.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::GRAY . "You must execute this command in-game");
            return;
        }
        if(!$sender->hasPermission("xpmoney.command"){
            $sender->sendMessage(TextFormat::GRAY . "You do not have permission to execute this command!");
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

           $form->setTitle("Transfer Economies");
           $form->addButton(TextFormat::AQUA . "EXP => Money\n", -1, "", "xpToMoney");
           $form->addButton(TextFormat::AQUA . "Money => EXP\n", -1, "", "moneyToXp");
           $form->setContent(TextFormat::GRAY . "Choose How You'd Like to Transfer\n" . TextFormat::AQUA . "Your EXP: " . number_format($exp) . "\n" . TextFormat::AQUA . "Your Money: " . $money);
           $this->sendForm($sender, $form);
    }

    public function sendXtmForm(Player $player): void {
        $eco = EconomyAPI::getInstance();
        $form = new CustomForm(function(Player $player, $data): void {
            if($data !== null){
                $amount = intval($data["amount"]);
                $cost = $this->getConfig()->get("xp-per-money-tick") * $amount;
                if($player->getCurrentTotalXp() - $cost < 0) {
                    $player->sendMessage(TextFormat::GRAY . "You do not have enough EXP for this action! " . TextFormat::GOLD . "EXP Needed: " . $cost);
                } else {
                    $eco->addMoney($player, $amount);
                    $player->subtractXp($cost);
                    $player->sendMessage(TextFormat::GRAY . "You have bought "  . TextFormat::AQUA . "$ $amount " . TextFormat::GRAY . "for " . TextFormat::AQUA . $cost . TextFormat::GRAY . " XP");
                }
                return;
            }
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::AQUA . "EXP => Money");
        $form->addLabel("§7EXP: $data §6Per: $1");
        $form->addSlider("Amount" , 1, 64, 1, 1, "amount");
        $this->sendForm($player, $form);
    }

}
