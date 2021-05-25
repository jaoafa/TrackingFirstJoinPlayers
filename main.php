<?php
/*
・毎日0時に前日のログイン人数と新規プレイヤーの一覧・ブロック設置破壊数をDiscordに送信する
・設置数より破壊数の方が多い場合は荒らし行為かも？と返す
*/

require_once(__DIR__ . "/vendor/autoload.php");
require_once(__DIR__ . "/lib/func.php");
require_once(__DIR__ . "/lib/DiscordEmbed.php");

$logger = initializeLogger();
$logger->info("Starting program");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required([
    "DISCORD_TOKEN",
    "DISCORD_CHANNEL",
    "MAINDB_HOST",
    "MAINDB_PORT",
    "MAINDB_NAME",
    "MAINDB_USER",
    "MAINDB_PASS",
    "HATDB_HOST",
    "HATDB_PORT",
    "HATDB_NAME",
    "HATDB_USER",
    "HATDB_PASS"
]);

$logger->info("Connecting databases...");

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => true,
];

$logger->info("Connecting jaoMain database...");
$main_dsn = "mysql:host={$_ENV["MAINDB_HOST"]};port={$_ENV["MAINDB_PORT"]};charset=utf8mb4;dbname={$_ENV["MAINDB_NAME"]}";
try {
    $main_pdo = new PDO($main_dsn, $_ENV["MAINDB_USER"], $_ENV["MAINDB_PASS"], $options);
} catch (PDOException $e) {
    $logger->critical("jaoMain database connection error.");
    exit(1);
}
$logger->info("jaoMain database connected.");

$logger->info("Connecting ZakuroHat database...");
$hat_dsn = "mysql:host={$_ENV["HATDB_HOST"]};port={$_ENV["HATDB_PORT"]};charset=utf8mb4;dbname={$_ENV["HATDB_NAME"]}";
try {
    $hat_pdo = new PDO($hat_dsn, $_ENV["HATDB_USER"], $_ENV["HATDB_PASS"], $options);
} catch (PDOException $e) {
    $logger->critical("ZakuroHat database connection error.");
    exit(1);
}
$logger->info("ZakuroHat database connected.");

// Get yesterday login users

$yesterday = date("Y-m-d", strtotime("-1 day"));

$stmt_count = $main_pdo->prepare("SELECT SUM(login_success = 1) as LoginSuccess, SUM(login_success = 0) as LoginFailure FROM login WHERE date REGEXP :date");
$stmt_count->bindValue(":date", $yesterday);
$stmt_count->execute();

$row = $stmt_count->fetch();
$success_count = $row["LoginSuccess"];
$failure_count = $row["LoginFailure"];
$total_count = $success_count + $failure_count;

$logger->info("login success: $success_count");
$logger->info("login failed: $failure_count");
$logger->info("total: $total_count");

$stmt = $main_pdo->prepare("SELECT DISTINCT player, uuid FROM login WHERE date REGEXP :date AND permission = :permission");
$stmt->bindValue(":date", $yesterday);
$stmt->bindValue(":permission", "Default");
$stmt->execute();

// Get users coreprotect data
$player_uuid = [];
$editeds = [];
$not_edited = [];
$warn_users = [];
while ($row = $stmt->fetch()) {
    $mcid = $row["player"];
    $uuid = $row["uuid"];
    $logger->info("$mcid ($uuid)");
    $player_uuid[$uuid] = $mcid;

    $cp = new CoreProtect($hat_pdo, $uuid);
    if (!$cp->isExists()) {
        $not_edited[] = $mcid;
        $logger->info("$mcid: not edited (isExists)");
        continue;
    }
    if ($cp->getPlaceCount() == 0 && $cp->getDestroyCount() == 0) {
        $not_edited[] = $mcid;
        $logger->info("$mcid: not edited (place&destroy)");
        continue;
    }
    if (($cp->getPlaceCount() - $cp->getPlaceRollbackCount()) < ($cp->getDestroyCount() - $cp->getDestroyRollbackCount())) {
        $warn_users[] = "{$mcid}(" . $cp->getPlaceCount() . " / " . $cp->getDestroyCount() . ")";
    }
    $logger->info("$mcid: edited ($cp)");

    $editeds[$uuid] = $cp;
}

// Summary of yesterday
$embed = new DiscordEmbed();
$embed->setTitle("Summary of yesterday");
$embed->setDescription("`$yesterday` のログインサマリー\n・プレイヤー数: `$total_count`回 (内ログイン失敗`{$failure_count}`回)");
$embed->setAuthor("jaotan", "https://jaoafa.com/", "https://jaoafa.com/favicons/android-chrome-512x512.png", "https://jaoafa.com/favicons/android-chrome-512x512.png");
foreach ($editeds as $uuid => $cp) {
    $mcid = $player_uuid[$uuid];
    $placeCount = $cp->getPlaceCount();
    $placeRBCount = $cp->getPlaceRollbackCount();
    $placeUnderlineStyle = ($placeCount - $placeRBCount) >= 1000 ? "__" : "";
    $placeBoldStyle = ($placeCount - $placeRBCount) >= 100 ? "**" : "";

    $destroyCount = $cp->getDestroyCount();
    $destroyRBCount = $cp->getDestroyRollbackCount();
    $destroyUnderlineStyle = ($destroyCount - $destroyRBCount) >= 1000 ? "__" : "";
    $destroyBoldStyle = ($destroyCount - $destroyRBCount) >= 100 ? "**" : "";

    $url = "https://admin.jaoafa.com/cp/$uuid";
    $embed->addFields(
        "`$mcid`",
        "ログ: $url\n設置: {$placeUnderlineStyle}{$placeBoldStyle}`{$placeCount}`{$placeBoldStyle}{$placeUnderlineStyle}回(内rb済: `{$placeRBCount}`回) / 破壊: {$destroyUnderlineStyle}{$destroyBoldStyle}`{$destroyCount}`{$destroyBoldStyle}{$destroyUnderlineStyle}回(内rb済: `{$destroyRBCount}`回)",
        false
    );
}
if (count($not_edited) != 0) {
    $embed->addFields(
        "その他",
        "設置破壊ログはありませんが、以下のプレイヤーがログインしました。\n`" . implode("`, `", $not_edited) . "`",
        false
    );
}
if (count($warn_users) != 0) {
    $embed->addFields(
        ":warning:注意",
        "以下のプレイヤーは__**設置数に対して破壊数が多い**__です！```\n" . implode("\n", $warn_users) . "\n```",
        false
    );
}

// Send message to discord
$data = [
    "content" => "",
    "embed" => $embed->Export()
];

$header = [
    "Content-Type: application/json",
    "Content-Length: " . strlen(json_encode($data)),
    "Authorization: Bot " . $_ENV["DISCORD_TOKEN"],
    "User-Agent: DiscordBot (https://jaoafa.com, v0.0.1)"
];

$context = [
    "http" => [
        "method" => "POST",
        "header" => implode("\r\n", $header),
        "content" => json_encode($data),
        "ignore_errors" => true
    ]
];

$context = stream_context_create($context);
$contents = file_get_contents("https://discord.com/api/channels/{$_ENV["DISCORD_CHANNEL"]}/messages", false, $context);
preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
$status_code = $matches[1];
if ($status_code == 200) {
    $logger->info("message send successful: {$http_response_header[0]}");
} else {
    $logger->error("message send failed: {$http_response_header[0]} {$contents}");
}
