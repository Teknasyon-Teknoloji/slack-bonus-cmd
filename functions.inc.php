<?php

function validateEmail($email)
{
    return $email && filter_var(mb_strtolower($email), FILTER_VALIDATE_EMAIL);
}

function getConfigList(\PDO $db)
{
    $configArray = array();
    $stmt = $db->query("SELECT * FROM config");
    $configList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($configList as $config) {
        $configArray[$config['ckey']] = $config['cval'];
    }
    return $configArray;
}

function sendMail($toList, $subject, $msg, $attachment = null)
{
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->IsSMTP();
    $mailer->SMTPAuth = true;
    $mailer->SMTPSecure = 'tls';
    $mailer->Host = SMTP_HOST;
    $mailer->Port = SMTP_PORT;
    $mailer->Username = SMTP_USERNAME;
    $mailer->Password = SMTP_PASSWORD;
    $mailer->CharSet = 'UTF-8';
    $mailer->From = SMTP_FROM;
    $mailer->FromName = SMTP_FROMNAME;

    foreach ($toList as $key => $value) {
        if (is_numeric($key)) { // $value => email
            $mailer->addAddress($value);
        } else { // $key => email, $value => name
            $mailer->addAddress($key, $value);
        }
    }

    if ($attachment) {
        $mailer->AddAttachment($attachment, basename($attachment));
    }
    $mailer->Subject = $subject;
    $mailer->msgHTML($msg);
    $mailer->send();
}

function sendResponseToSlack($responseUrl, $msg, $logId)
{
    $ch = curl_init();
    $payload = json_encode(array("text" => $msg));
    curl_setopt($ch, CURLOPT_URL, $responseUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    error_log('[' . $logId . '][INFO] Slack response: ' . json_encode($result));
}

function updateUserInfo(\PDO $db, $idList)
{
    $result = array();
    foreach ($idList as $slackId) {
        if (validateEmail($slackId)) {
            continue;
        }
        $slackInfo = json_decode(file_get_contents(
            'https://slack.com/api/users.info?'
            . 'token=' . SLACK_API_TOKEN
            . '&user=' . urlencode($slackId)
        ), true);
        if ($slackInfo && $slackInfo['ok'] && isset($slackInfo['user'])) {
            $stmt = $db->prepare(
                "REPLACE INTO slack_user_info (`slack_id`, `name`, `surname`, `email`)"
                . " VALUES (:slackid,:namee, :surname,:email)"
            );
            $stmt->execute(
                array(
                    ':slackid' => $slackInfo['user']['id'],
                    ':namee' => $slackInfo['user']['profile']['first_name'],
                    ':surname' => $slackInfo['user']['profile']['last_name'],
                    ':email' => $slackInfo['user']['profile']['email']
                )
            );
            $result[$slackId] = array(
                'name' => $slackInfo['user']['profile']['first_name'],
                'surname' => $slackInfo['user']['profile']['last_name'],
                'email' => $slackInfo['user']['profile']['email']
            );
        }
    }
    return $result;
}

function getUserEmail(\PDO $db, $slackUserId)
{
    $stmtu = $db->prepare(
        "SELECT email FROM slack_user_info WHERE slack_id=:userid"
    );
    $stmtu->execute(array(':userid' => $slackUserId));
    $email = $stmtu->fetchColumn(0);
    if (!$email) {
        $userInfo = updateUserInfo($db, array($slackUserId));
        if ($userInfo) {
            $email = $userInfo[$slackUserId]['email'];
        }
    }
    return $email;
}
