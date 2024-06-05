<?php

namespace XPocketMC\VoiceChat;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

class Main extends PluginBase implements MessageComponentInterface, Listener {

    private $clients;
    private $enabledPlayers;

    public function onEnable(): void {
        $this->getLogger()->info("Enabling VoiceChat Plugin...");

        $this->clients = new \SplObjectStorage;
        $this->enabledPlayers = new Config($this->getDataFolder() . "enabledPlayers.yml", Config::YAML);

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this
                )
            ),
            8080
        );

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new class($server) extends \pocketmine\scheduler\Task {
            private $server;

            public function __construct($server) {
                $this->server = $server;
            }

            public function onRun(): void {
                $this->server->loop->tick();
            }
        }, 1);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getLogger()->info("VoiceChat Plugin Enabled!");
    }

    public function onDisable(): void {
        $this->getLogger()->info("Disabling VoiceChat Plugin...");
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->getLogger()->info("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $playerName = $data['player'];
        $audioData = $data['audio'];

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode(['player' => $playerName, 'audio' => $audioData]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->getLogger()->info("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->getLogger()->error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "voicechat") {
            if ($sender instanceof Player) {
                $playerName = $sender->getName();
                if ($this->enabledPlayers->exists($playerName)) {
                    $this->enabledPlayers->remove($playerName);
                    $sender->sendMessage("Voice chat disabled!");
                } else {
                    $this->enabledPlayers->set($playerName, true);
                    $sender->sendMessage("Voice chat enabled!");
                }
                $this->enabledPlayers->save();
                return true;
            } else {
                $sender->sendMessage("This command can only be used by players.");
                return false;
            }
        }
        return false;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $player->sendMessage("Use /voicechat to toggle voice chat.");
    }

    public function onQuit(PlayerQuitEvent $event) {
        $playerName = $event->getPlayer()->getName();
        if ($this->enabledPlayers->exists($playerName)) {
            $this->enabledPlayers->remove($playerName);
            $this->enabledPlayers->save();
        }
    }
}