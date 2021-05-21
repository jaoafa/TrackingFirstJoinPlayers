<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

function initializeLogger()
{
    $logger = new Logger("TrackingFirstJoinPlayers");
    $logger->setTimezone(new \DateTimeZone("Asia/Tokyo"));
    $handlers = [
        new StreamHandler("php://stdout", Logger::INFO, false),
        new StreamHandler("php://stdout", Logger::NOTICE, false),
        new StreamHandler("php://stderr", Logger::WARNING, false),
        new StreamHandler("php://stderr", Logger::ERROR, false),
        new StreamHandler("php://stderr", Logger::CRITICAL, false),
        new StreamHandler("php://stderr", Logger::ALERT, false),
        new StreamHandler("php://stderr", Logger::EMERGENCY, false),
        new RotatingFileHandler("logs/log.log", 30),
    ];
    $formatter = new LineFormatter("[%datetime%] %level_name%: %message%\n", "Y-m-d H:i:s", true);
    foreach ($handlers as $hander) {
        $hander->setFormatter($formatter);
        $logger->pushHandler($hander);
    }
    return $logger;
}

class CoreProtect
{
    private $userId = null;
    private $placeCount = 0;
    private $destroyCount = 0;
    private $placeRollbackCount = 0;
    private $destroyRollbackCount = 0;

    public function __construct(\PDO $pdo, $uuid)
    {
        $stmt = $pdo->prepare("SELECT * FROM co_user WHERE uuid = :uuid");
        $stmt->bindValue(":uuid", $uuid);
        $stmt->execute();

        $row = $stmt->fetch();
        if ($row == false) {
            return;
        }
        $this->userId = $row["rowid"];

        $stmt = $pdo->prepare("SELECT COUNT(rowid) as allCount, SUM(action = 1) as placeCount, SUM(action = 0) as destroyCount, SUM(rolled_back = 1 and action = 1) as placeRollbackCount, SUM(rolled_back = 1 and action = 0) as destroyRollbackCount FROM co_block WHERE user = :userId");
        $stmt->bindValue(":userId", $this->userId);
        $stmt->execute();

        $row = $stmt->fetch();
        if ($row == false) {
            return;
        }
        $this->placeCount = $row["placeCount"];
        $this->destroyCount = $row["destroyCount"];
        $this->placeRollbackCount = $row["placeRollbackCount"];
        $this->destroyRollbackCount = $row["destroyRollbackCount"];
    }

    public function isExists()
    {
        return $this->userId != null;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getPlaceCount()
    {
        return $this->placeCount;
    }

    public function getDestroyCount()
    {
        return $this->destroyCount;
    }

    public function getPlaceRollbackCount()
    {
        return $this->placeRollbackCount;
    }

    public function getDestroyRollbackCount()
    {
        return $this->destroyRollbackCount;
    }

    public function __toString()
    {
        return "CoreProtect{userId={$this->userId}, placeCount={$this->placeCount}, destroyCount={$this->destroyCount}, placeRollbackCount={$this->placeRollbackCount}}";
    }
}
