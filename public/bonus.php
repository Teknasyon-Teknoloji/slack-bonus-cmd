<?php

try {
    include '../vendor/autoload.php';
    include '../config.inc.php';
    include BASE_PATH . 'functions.inc.php';

    error_log('[' . LOG_ID . '][INFO] New request from ' . $_SERVER['REMOTE_ADDR'] . ': ' . json_encode($_REQUEST));

    if ($_REQUEST['token'] !== REQUEST_TOKEN) {
        error_log('[' . LOG_ID . '][ERROR] Token failed!');
        exit;
    }

    $winner = null;
    $winnerId = null;
    $payer = strtolower($_REQUEST['user_name']);
    $payerId = trim($_REQUEST['user_id']);
    if (trim($_REQUEST['text']) == 'rapor') {
        $info = 'rapor';
    } else {
        list($winner, $info) = explode(' ', trim($_REQUEST['text']), 2);
        list($winnerId, $winner) = explode('|', trim($winner), 2);
        $winner = str_replace(array('<', '>'), array('', ''), $winner);
        $winnerId = str_replace(array('<', '>'), array('', ''), $winnerId);
        $winnerId = str_replace('mailto:', '', ltrim($winnerId, '@'));
        $info = trim($info);
    }

    if (!$payer) {
        error_log('[' . LOG_ID . '][ERROR] Payer username not found!');
        exit;
    }

    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USERNAME,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $primConfig = getConfigList($db);

    if ($info == '') {
        $stmt = $db->prepare(
            "SELECT prim_date,count(id) AS toplam FROM prim WHERE user=:user AND status=1 AND prim_date>=:pdate"
            . " GROUP BY prim_date ORDER BY prim_date DESC"
        );
        $stmt->execute(array(':user' => $payer, ':pdate' => date('Y-01-01')));
        $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $yearCount = 0;
        $monthCount = 0;
        foreach ($userStats as $userStat) {
            if ($userStat['prim_date'] == date('Y-m-01')) {
                $monthCount = $userStat['toplam'];
            }
            $yearCount += $userStat['toplam'];
        }
        $response = 'Aylık Durum : Verilen = ' . $monthCount
            . ', Kalan = ' . ($primConfig['prim.limit.monthly'] - $monthCount)
            . PHP_EOL;
        $response .= 'Yıllık Durum : Verilen = ' . $yearCount
            //. ', Kalan = ' . ($primConfig['prim.limit.yearly'] - $yearCount)
            . PHP_EOL;
    } elseif (substr($info, 0, strlen('rapor')) == 'rapor' && in_array($payer, $SLACK_ADMIN_USERNAMES)) {
        $reportFile = BASE_PATH . 'log/tesekkur_bonus_rapor.' . date('dmYHis') . '.csv';
        $stmt = $db->query(
            "SELECT CONCAT('\"',user, '\";\"', winner, '\";\"',"
            . " date_format(creation_date,'%d.%m.%Y %H:%i:%s'),'\";\"',amount,'\";\"',info,'\"') AS csv"
            . " FROM prim WHERE status=1"
        );
        $rapor = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $rapor .= '"Çalışan";"Kazanan";"Tarih";"Miktar";"Açıklama"' . PHP_EOL;
        $raporRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($raporRows as $raporRow) {
            $rapor .= $raporRow['csv'] . PHP_EOL;
        }
        @file_put_contents($reportFile, $rapor);
        if (is_file($reportFile)) {
            $email = getUserEmail($db, $payerId);

            if ($email) {
                sendMail(
                    array($email),
                    'Teşekkür Bonusu Rapor',
                    'Teşekkür bonusu raporu ektedir.',
                    $reportFile
                );
                $response = 'Rapor eposta adresine gönderildi.';
            } else {
                $response = 'Rapor gönderilemedi. TEKRAR DENEYINIZ (' . $reportFile . ')';
            }
            @unlink($reportFile);
        } else {
            $response = 'Rapor dosyası oluşturulamadı. TEKRAR DENEYINIZ (' . $reportFile . ')';
        }
    } else {
        if (!$winnerId || !$winner) {
            error_log('[' . LOG_ID . '][ERROR] Winner username not found!');
            $response = 'Kullanıcı adı bulunamadı. TEKRAR DENEYİNİZ!';
        } elseif ($winnerId == $payerId) {
            error_log('[' . LOG_ID . '][ERROR] Winner and payer are same!');
            $response = 'Tüm şirkete kahve ısmarlama isteğiniz alındı. Eposta ile tüm şirkete bilgi verilecek. Teşekkürler.';
        } else {
            $stmtu = $db->prepare(
                "SELECT * FROM slack_user_info WHERE (slack_id=:userid OR slack_id=:winnerid)"
                . " AND email IS NOT NULL AND email!=''"
            );
            $stmtu->execute(array(':userid' => $payerId, ':winnerid' => $winnerId));
            $userInfos = $stmtu->fetchAll(PDO::FETCH_ASSOC);
            if (count($userInfos) != 2) {
                updateUserInfo($db, array($payerId, $winnerId));
            }

            $stmt = $db->prepare(
                "INSERT INTO prim (`user`, `user_slack_id`, `winner`, `winner_slack_id`, `prim_date`, `creation_date`, `amount`, `info`, `slack_response_url`)"
                . " VALUES (:user,:userid, :winner,:winnerid, :prim_date,:creation_date,:amount,:info, :response_url)"
            );
            $stmt->execute(
                array(
                    ':user' => $payer,
                    ':userid' => $payerId,
                    ':winner' => $winner,
                    ':winnerid' => $winnerId,
                    ':prim_date' => date('Y-m-01'),
                    ':creation_date' => date('Y-m-d H:i:s'),
                    ':amount' => $primConfig['prim.amount'],
                    ':info' => $info,
                    ':response_url' => trim($_REQUEST['response_url']),
                )
            );
            $affectedRows = $stmt->rowCount();
            if ($affectedRows > 0) {
                $response = (validateEmail($winner) === false ? '<@' . $winnerId . '>' : '"' . $winner . '"')
                    . ' için "Teşekkür Bonusu" kaydınız alındı. [#' . $db->lastInsertId() . ']';
            } else {
                $response = 'Kaydınız alınamadı. TEKRAR DENEYİNİZ!';
            }
        }
    }
    error_log('[' . LOG_ID . '][INFO] Response: "' . $response . '"');
    echo $response;
} catch (\Exception $e) {
    error_log('[' . LOG_ID . '][CRITICAL] Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
}
