<?php

namespace CoinSystem;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class Main extends PluginBase
{
    private Config $coinData;

    public function onEnable(): void
    {
        @mkdir($this->getDataFolder());
        $this->coinData = new Config($this->getDataFolder() . "coins.yml", Config::YAML);
        $this->getLogger()->info("Coin sistemi aktif edildi!");
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
    {
        if ($cmd->getName() === "coin") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("Bu komudu oyunda kullanınız.");
                return true;
            }

            $this->coinData->reload();
            $isim = strtolower($sender->getName());

            if (!$this->coinData->exists($isim)) {
                $this->coinData->set($isim, 0);
                $this->coinData->save();
            }

            $miktar = $this->coinData->get($isim);
            $sender->sendMessage(TextFormat::BLUE . "Coin miktarın: " . TextFormat::YELLOW . $miktar);
            return true;
        }

        else if ($cmd->getName() === "coingonder") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("Bu komudu oyunda kullan.");
                return true;
            }

            if (!isset($args[0]) || !isset($args[1])) {
                $sender->sendMessage("Kullanım: /coingonder <oyuncu> <miktar>");
                return true;
            }

            $hedefIsim = $args[0];
            $miktar = (int)$args[1];

            $hedefOyuncu = $this->getServer()->getPlayerExact($hedefIsim);
            if ($hedefOyuncu === null) {
                $sender->sendMessage(TextFormat::RED . "Oyuncu çevrimiçi değil veya böyle bir oyuncu yok!");
                return true;
            }

            if ($miktar < 1) {
                $sender->sendMessage(TextFormat::RED . "Geçerli bir miktar gir.");
                return true;
            }

            $gonderen = strtolower($sender->getName());
            $alici = strtolower($hedefOyuncu->getName());

            if ($gonderen === $alici) {
                $sender->sendMessage(TextFormat::RED . "Kendine coin gönderemezsin.");
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
                $sender->sendMessage(TextFormat::RED . "Bu kadar coinin yok.");
                return true;
            }

            $this->coinData->set($gonderen, $gonderenCoin - $miktar);
            $this->coinData->set($alici, $alicicoin + $miktar);
            $this->coinData->save();

            $sender->sendMessage("§aBaşarıyla §e$miktar §acoin gönderildi!");
            $hedefOyuncu->sendMessage("§e$gonderen §asadeli sana §e$miktar §acoin gönderdi!");
            return true;
        }

        else if ($cmd->getName() === "coinmarket") {
            if (!$sender instanceof Player) {
                $sender->sendMessage(TextFormat::RED . "Bu komudu oyunda kullan.");
                return true;
            }

            $market = new CoinMarket($this, $this->coinData);
            $market->openForm($sender);
            return true;
        }

        if ($cmd->getName() === "coinayarla") {
            if (!$sender->hasPermission("coin.ayarla")) {
                $sender->sendMessage(TextFormat::RED . "Bu komudu kullanmak için yetkin yok!");
                return true;
            }

            if (count($args) < 2) {
                $sender->sendMessage("Kullanım: /coinayarla <oyuncu> <miktar>");
                return true;
            }

            $coinayarla = strtolower($args[0]);
            $amount = (int)$args[1];

            if (!is_numeric($args[1]) || $amount < 0) {
                $sender->sendMessage(TextFormat::RED . "Geçerli bir miktar giriniz.");
                return true;
            }

            $targetPlayer = $this->getServer()->getPlayerExact($coinayarla);
            if ($targetPlayer === null) {
                $sender->sendMessage(TextFormat::RED . "Bu oyuncu daha önce sunucuya hiç girmemiş!");
                return true;
            }

            if(!$this->coinData->exists($coinayarla)) {
                $sender->sendMessage(TextFormat::RED . "$coinayarla isimli oyuncu sunucuya hiç girmemiş");
                return true;
            }

            $this->coinData->set($coinayarla, $amount);
            $this->coinData->save();
            $sender->sendMessage("§e$coinayarla §radlı oyuncunun coini §a$amount §rolarak ayarlandı.");
            return true;
        }

        return false;
    }
}
