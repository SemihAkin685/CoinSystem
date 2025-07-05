<?php

namespace CoinSystem;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\utils\TextFormat;

class CoinMarket
{

    private PluginBase $plugin;
    private Config $coinData;
    private Config $config;

    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
        $this->coinData = new Config($this->plugin->getDataFolder() . "coins.yml", Config::YAML);
        $this->config = new Config($this->plugin->getDataFolder() . "coinmarket.yml", Config::YAML);
    }

    public function openForm(Player $player): void
    {
        $items = $this->config->get("items");
        if (empty($items) || !is_array($items)) {
            $player->sendMessage(TextFormat::RED . "CoinMarket şu anda boş veya hatalı.");
            return;
        }
        $form = new SimpleForm(function (Player $player, $data = null) use ($items): void {
            if ($data === null) return;
            $keys = array_keys($items);
            if (!isset($keys[$data])) {
                $player->sendMessage(TextFormat::RED . "Geçersiz seçim yaptınız.");
                return;
            }
            $selectedKey = $keys[$data];
            $selectedItem = $items[$selectedKey];
            $itemString = $selectedItem["item"] ?? "";
            $item = StringToItemParser::getInstance()->parse($itemString);
            if ($item === null) {
                $player->sendMessage(TextFormat::RED . "Geçersiz eşya: " . $itemString);
                return;
            }
            $itemName = $selectedItem["isim"] ?? "Bilinmeyen Eşya";
            $item->setCustomName($itemName);
            $price = (int)($selectedItem["fiyat"] ?? 0);
            $playerName = strtolower($player->getName());
            $currentCoins = (int)$this->coinData->get($playerName, 0);
            if ($currentCoins < $price) {
                $player->sendMessage(TextFormat::RED . "Bu eşyayı almak için yeterli coinin yok.");
                return;
            }
            $enchants = $selectedItem["buyu"] ?? [];
            foreach ($enchants as $id => $enchantData) {
                $enchantId = strtolower($enchantData["id"] ?? "");
                $level = (int)($enchantData["level"] ?? 1);
                $enchant = match ($enchantId) {
                    "protection" => VanillaEnchantments::PROTECTION(),
                    "unbreaking" => VanillaEnchantments::UNBREAKING(),
                    "sharpness" => VanillaEnchantments::SHARPNESS(),
                    "efficiency" => VanillaEnchantments::EFFICIENCY(),
                    "fire_aspect" => VanillaEnchantments::FIRE_ASPECT(),
                    default => null
                };
                if ($enchant !== null) {
                    $item->addEnchantment(new EnchantmentInstance($enchant, $level));
                }
            }
            $this->coinData->set($playerName, $currentCoins - $price);
            $this->coinData->save();
            $player->getInventory()->addItem($item);
            $player->sendMessage("§aÜrünü başarıyla satın aldın. §e" . $itemName);
        });
        $form->setTitle("CoinMarket Menüsü");
        foreach ($items as $key => $itemData) {
            $isim = $itemData["isim"] ?? "Bilinmeyen";
            $fiyat = $itemData["fiyat"] ?? "0";
            $form->addButton("{$isim} - {$fiyat} coin");
        }
        $form->sendToPlayer($player);
    }
}