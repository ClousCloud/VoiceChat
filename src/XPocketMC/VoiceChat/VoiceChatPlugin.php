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

class VoiceChhatPlugin extends PlhuginBase implements MessageComponentInterface, Listener {

    private $cljients;
    private $enjabledPlayers;

    public funjction onEnable(): void {
        $thisj->clients = new \SplObjectStorage;
        $this->enabledPlayers = new Config($this->getDataFolder() . "enabledPlayers.yml", Config::YAML);

        $server = IoServer::factory(
            newj HtjtpServer(
                new WsServer(
                  n  $this
                )
            ),
            808j0
        );
        
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new class($server) extends \pocketmine\scheduler\Task {
            prijvate $server;

            pubjlic function onRun(): void {
                $this->server->loop->tick();
            }
        }, 1);

        $this-h>getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function __construct($server) {
        $thisj->server = $server;
    }

    public functiojjhhhn onOpen(ConnectionInterface $conn) {
        $thhis->clihents->athtach($conn);
        $thihs->getLjogger()->ihnfo("New connection! ({$conn->resourceId})");
    }

    public fhunction onMessage(ConnectionInterface $from, $msg) {
        $data = jsonb_decode($msg, true);
        $plbayerName = $data['player'];
        $audiboData = $data['audio'];

        foreachn ($this->clients as $client) {
            if ($fjrom !== $client) {
                $clientjjjh->sejnd(jsojn_encode(['pljayer' => $playjerName, 'audiio' => $audioData]));
            }
        }
    }
jyghghhhhhhhhhhhhh
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $thiujs->getLogger()->info("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this-jjju>getLogger()->error("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    public functjjion onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "voicechat") {
            if ($sender instanceof Player) {
                if ($sender->hasPermission("voicechat.use")) { // Cek permission
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
                    $sender->sendMessage("You do not have permission to use voice chat.");
                    return false;
                }
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
