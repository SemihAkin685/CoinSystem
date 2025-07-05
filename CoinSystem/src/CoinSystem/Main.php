<?php

namespace CoinSystem;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\jojoe77777\SimpleForm;

class Main extends PluginBase
{
    private Config $coinData;
    private Config $config;

    public function onEnable(): void
    {
        @mkdir($this->getDataFolder());
        $this->coinData = new Config($this->getDataFolder() . "coins.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder() . "coinmarket.yml", Config::YAML);
        $this->getLogger()->info("Coin sistemi aktif edildi!");
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
    {
        if ($cmd->getName() === "coin") {
            $this->coinData->reload();
            if (!$sender instanceof Player) {
                $sender->sendMessage("Bu komudu oyunda kullanınız.");
                return true;
            }
            $isim = strtolower($sender->getName());

            if (!$this->coinData->exists($isim)) {
                $this->coinData->set($isim, 0);
                $this->coinData->save();
            }

            $miktar = $this->coinData->get($isim);
            $sender->sendMessage(TextFormat::BLUE . "Coin miktarın: " . TextFormat::YELLOW . $miktar);
        }
        if ($cmd->getName() === "coinver") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("Bu komudu oyunda kullan.");
                return true;
            }

            if (!isset($args[0]) || !isset($args[1])) {
                $sender->sendMessage("Kullanım: /coinver <oyuncu> <miktar>");
                return true;
            }

            $hedefIsim = $args[0];
            $miktar = (int)$args[1];

            $hedefOyuncu = $this->getServer()->getPlayerExact($hedefIsim);
            if ($hedefOyuncu === null) {
                $sender->sendMessage("Oyuncu çevrimiçi değil.");
                return true;
            }

            if ($miktar < 1) {
                $sender->sendMessage("Geçerli bir miktar gir.");
                return true;
            }

            $gonderen = strtolower($sender->getName());
            $alici = strtolower($hedefOyuncu->getName());

            if ($gonderen === $alici) {
                $sender->sendMessage("Kendine coin gönderemezsin.");
                return true;
            }

            if (!$this->coinData->exists($gonderen)) {
                $this->coinData->set($gonderen, 0);
            }

            if (!$this->coinData->exists($alici)) {
                $this->coinData->set($alici, 0);
            }

            $gonderenCoin = $this->coinData->get($gonderen);
            $alicicoin = $this->coinData->get($alici);

            if ($gonderenCoin < $miktar) {
                $sender->sendMessage("Bu kadar coinin yok.");
                return true;
            }

            $this->coinData->set($gonderen, $gonderenCoin - $miktar);
            $this->coinData->set($alici, $alicicoin + $miktar);
            $this->coinData->save();

            $sender->sendMessage("§aBaşarıyla §e$miktar §acoin gönderildi!");
            $hedefOyuncu->sendMessage("§e$gonderen §asadeli sana §e$miktar §acoin gönderdi!");
            return true;
        }

        if ($cmd->getName() === "coinmarket") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("Bu komudu oyunda kullan.");
                return true;
            }

            $market = new CoinMarket($this);
            $market->openForm($sender);
            return true;
        }

        if ($cmd->getName() === "coinayarla") {
            if (count($args) < 2) {
                $sender->sendMessage("Kullanım: /coinayarla <oyuncu> <miktar>");
                return true;
            }

            $coinayarla = strtolower($args[0]);
            $amount = (int)$args[1];
            if(!is_numeric($args[1]) || $amount < 0 ) {
                $sender->sendMessage(TextFormat::RED . "Geçerli bir miktar giriniz.");
                return true;
            }
            $this->coinData->set($coinayarla, $amount);
            $this->coinData->save();
            $sender->sendMessage("§e$coinayarla §radlı oyuncunun coini §a$amount §rolarak ayarlandı.");
        }

        return false;
    }
}
