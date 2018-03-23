<?php

try {

    include 'vendor/autoload.php';
    include 'config.inc.php';
    include BASE_PATH . 'functions.inc.php';

    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USERNAME,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $primConfig = getConfigList($db);

    $stmt = $db->query("SELECT * FROM prim WHERE status=0");
    $waitingPrims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($waitingPrims as $waitingPrim) {

        $stmtu = $db->prepare(
            "SELECT * FROM slack_user_info WHERE slack_id=:userid OR slack_id=:winnerid"
        );
        $stmtu->execute(array(':userid' => $waitingPrim['user_slack_id'],':winnerid' => $waitingPrim['winner_slack_id']));
        $dbUserInfos = $stmtu->fetchAll(PDO::FETCH_ASSOC);
        $userInfos = array();
        foreach ($dbUserInfos as $userInfo) {
            $userInfos[ $userInfo['slack_id'] ] = $userInfo;
        }

        $stmt2 = $db->prepare(
            "SELECT prim_date,count(id) as toplam FROM prim WHERE user=:user AND status=1 AND prim_date>=:pdate"
            ." GROUP BY prim_date ORDER BY prim_date DESC"
        );
        $stmt2->execute(array(':user' => $waitingPrim['user'],':pdate' => date('Y-01-01')));
        $userStats = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $yearCount = 0;
        foreach ($userStats as $userStat) {
            if ($userStat['prim_date']==date('Y-m-01') && $userStat['toplam']>=$primConfig['prim.limit.monthly']) {
                $db->query("UPDATE prim SET status=-1 WHERE id=" . $waitingPrim['id']);
                sendResponseToSlack($waitingPrim['slack_response_url'], 'Aylık "Teşekkür Bonusu" limitiniz doldu! [#'. $waitingPrim['id'] .']', LOG_ID);
                continue 2;
            }
            $yearCount += $userStat['toplam'];
        }
        if ($yearCount>=$primConfig['prim.limit.yearly']) {
            $db->query("UPDATE prim SET status=-2 WHERE id=" . $waitingPrim['id']);
            sendResponseToSlack($waitingPrim['slack_response_url'], 'Yıllık "Teşekkür Bonusu" limitiniz doldu! [#'. $waitingPrim['id'] .']', LOG_ID);
            continue;
        }
        $db->query("UPDATE prim SET status=1 WHERE id=" . $waitingPrim['id']);
        sendResponseToSlack(
            $waitingPrim['slack_response_url'],
            '"Teşekkür Bonusu" bildiriminiz işlendi. [#'. $waitingPrim['id'] .']',
            LOG_ID
        );
        $payerName = isset($userInfos[ $waitingPrim['user_slack_id'] ])
            ?$userInfos[ $waitingPrim['user_slack_id'] ]['name']. ' ' . $userInfos[ $waitingPrim['user_slack_id'] ]['surname']
            :$waitingPrim['user'];
        $payerEmail = isset($userInfos[ $waitingPrim['user_slack_id'] ])
            ?$userInfos[ $waitingPrim['user_slack_id'] ]['email']:null;
        $winnerName = isset($userInfos[ $waitingPrim['winner_slack_id'] ])
            ?$userInfos[ $waitingPrim['winner_slack_id'] ]['name']. ' ' . $userInfos[ $waitingPrim['winner_slack_id'] ]['surname']
            :$waitingPrim['user'];
        $winnerEmail = isset($userInfos[ $waitingPrim['winner_slack_id'] ])
            ?$userInfos[ $waitingPrim['winner_slack_id'] ]['email']:(validateEmail($waitingPrim['winner_id'])?$waitingPrim['winner_id']:null);

        sendMail(
            $ADMIN_MAILS,
            '"Teşekkür Bonusu" Onayı - ' . $payerName . ' [#'. $waitingPrim['id'] .']',
            '"' . $payerName . '", "'. $winnerName .'" çalışanına '
            . $waitingPrim['amount'] . ' TL. "Teşekkür Bonusu" verdi.<br/>' . PHP_EOL
            . 'Açıklama: ' . $waitingPrim['info']
        );
        if ($winnerEmail) {
            sendMail(
                array(
                    $winnerEmail
                ),
                'Tebrikler "' . $payerName . '" size "Teşekkür Bonusu" verdi. [#'. $waitingPrim['id'] .']',
                '"' . $payerName . '", size '
                . $waitingPrim['amount'] . ' TL. "Teşekkür Bonusu" verdi.<br/>' . PHP_EOL
                . 'Açıklama: ' . $waitingPrim['info']
            );
        }
    }
} catch (\Exception $e) {
    error_log('[' . LOG_ID . '][CRITICAL] Exception: ' . $e->getMessage(). ' ' . $e->getTraceAsString());
}
