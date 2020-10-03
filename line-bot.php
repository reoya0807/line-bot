<?php

$accessToken = '1OMBjCssnN2Br7X2oq376j3qLhJ13MLmGHxuf0hg0c5ZMAw4xLy25/sg63POGslgLq4oDDT5JzMNA5DVFExWVBt04VPIY37jrJjtpB6a2GyB9m4t0JDEIhTX2WSMx/sFyNyL/MVL5yaP9hrLm03WDwdB04t89/1O/w1cDnyilFU=';
$headers = [
  'Authorization: Bearer '.$accessToken,
  'Content-Type: application/json; charset=utf-8',
];

// LINEからのコールバックデータ取得
$json_string = file_get_contents('php://input');
$jsonObj = json_decode($json_string);

// ReplyToken取得
$replyToken = $jsonObj->events[0]->replyToken;
// webhookイベントタイプ
$type = $jsonObj->events[0]->type;
// ユーザID、設定時間、フラグ
$user_id = $jsonObj->events[0]->source->userId."\n";
$startday = "2020-10-01\n";
$settime = "0\n";
$taken = "0\n";
$takenok = 0;

if ($type == 'follow') {
  // ユーザID、設定時間、フラグをファイルに保存
  file_put_contents("user.lst", $user_id, FILE_APPEND);
  file_put_contents("startday.lst", $startday, FILE_APPEND);
  file_put_contents("settime.lst", $settime, FILE_APPEND);
  file_put_contents("taken.lst", $taken, FILE_APPEND);
} else if ($type == 'unfollow') {
  $buf1 = "";
  $buf2 = "";
  $buf3 = "";
  $buf4 = "";
  $users = file('user.lst');
  $startdays = file('startday.lst');
  $settimes = file('settime.lst');
  $takens = file('taken.lst');

  foreach ($users as $key => $user) {
    if ($user != $user_id) {
      $buf1 .= $user;
      $buf2 .= $startdays[$key];
      $buf3 .= $settimes[$key];
      $buf4 .= $takens[$key];
    }
  }
  // ファイルに保存
  file_put_contents('user.lst.wk', $buf1);
  file_put_contents('startday.lst.wk', $buf2);
  file_put_contents('settime.lst.wk', $buf3);
  file_put_contents('taken.lst.wk', $buf4);

  // リネーム
  exec('mv -f user.lst.wk user.lst');
  exec('mv -f startday.lst.wk startday.lst');
  exec('mv -f settime.lst.wk settime.lst');
  exec('mv -f taken.lst.wk taken.lst');
} else if ($type == 'message') {
  $takenok = 1;

  // webhookイベントタイプ
  $messageType = $jsonObj->events[0]->message->type;
  // テキストメッセージ以外のときは何も返さず終了
  if ($messageType != 'text') {
   exit;
  }
  // メッセージ取得
  $text = $jsonObj->events[0]->message->text;
  if ((strpos($text, "飲") !== false || strpos($text, "のん") !== false || strpos($text, "のみ") !== false) && strpos($text, "ない") === false) {
    // POSTデータを設定
    $post = [
      'replyToken' => $replyToken,
      'messages' => [
        [
          'type' => 'text',
          'text' => "えらい！",
        ],
      ],
    ];
    $buf = "";
    $users = file('user.lst');
    $takens = file('taken.lst');

    foreach ($users as $key => $user) {
      if ($user != $user_id) {
        $buf .= $takens[$key];
      } else {
        $buf .= "0\n";
      }
    }
    // ファイルに保存
    file_put_contents('taken.lst.wk', $buf);
    // リネーム
    exec('mv -f taken.lst.wk taken.lst');
  } else if (strpos($text, "start ") !== false) {
    $startday = substr($text, 6);

    // POSTデータを設定
    $post = [
      'replyToken' => $replyToken,
      'messages' => [
        [
          'type' => 'text',
          'text' => "薬を飲み始めた日を".$startday."に設定しました",
        ],
      ],
    ];
    $buf = "";
    $users = file('user.lst');
    $startdays = file('startday.lst');

    foreach ($users as $key => $user) {
      if ($user != $user_id) {
        $buf .= $startdays[$key];
      } else {
        $buf .= $startday."\n";
      }
    }
    // ファイルに保存
    file_put_contents('startday.lst.wk', $buf);
    // リネーム
    exec('mv -f startday.lst.wk startday.lst');
  } else if (strpos($text, "set ") !== false) {
    $settime = substr($text, 4);

    // POSTデータを設定
    $post = [
      'replyToken' => $replyToken,
      'messages' => [
        [
          'type' => 'text',
          'text' => $settime."時に設定しました",
        ],
      ],
    ];
    $buf = "";
    $users = file('user.lst');
    $settimes = file('settime.lst');

    foreach ($users as $key => $user) {
      if ($user != $user_id) {
        $buf .= $settimes[$key];
      } else {
        $buf .= $settime."\n";
      }
    }
    // ファイルに保存
    file_put_contents('settime.lst.wk', $buf);
    // リネーム
    exec('mv -f settime.lst.wk settime.lst');
  } else if ($text == "help") {
    $users = file('user.lst');
    $startdays = file('startday.lst');
    $settimes = file('settime.lst');

    foreach ($users as $key => $user) {
      if ($user == $user_id) {
        $startday = $startdays[$key];
        $settime = substr($settimes[$key], 0, -1);
      }
    }

    $today = date("Y-m-d");
    $startday = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - (strtotime($today) - strtotime($startday)) / 86400 % 28, date('Y')));
    // POSTデータを設定
    $postText = "薬を飲み始めた日は".$startday."に設定されています。\n";
    $postText .= "薬を飲む時間は".$settime."時に設定されています。\n\n";
    $postText .= "薬を飲み始めた日を変更したい場合は「start 2020-10-01」のようにしてメッセージで送信すると変更できます。\n";
    $postText .= "startと日付の間に半角スペースが必要なので記入に注意してください。また日付はスラッシュではなくハイフンで入力してください。\n\n";
    $postText .= "薬を飲む時間を変更したい場合は「set 22」のようにしてメッセージで送信すると変更できます。\n";
    $postText .= "setと時間の間に半角スペースが必要なので記入に注意してください。\n\n";
    $postText .= "設定の変更に成功すると、完了メッセージが送信されます。メッセージが送られてこない場合は、記入に誤りがある可能性があります。";
    $post = [
      'replyToken' => $replyToken,
      'messages' => [
        [
          'type' => 'text',
          'text' => $postText,
        ],
      ],
    ];
  }
  // POSTデータをJSONにエンコード
  $post = json_encode($post);
  $ch = curl_init('https://api.line.me/v2/bot/message/reply');
  $options = [
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_BINARYTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_POSTFIELDS => $post,
  ];
  // 実行
  curl_setopt_array($ch, $options);
  $result = curl_exec($ch);
}

$users = file('user.lst');
$startdays = file('startday.lst');
$settimes = file('settime.lst');
$takens = file('taken.lst');

$buf = "";
$today = date("Y-m-d");
// 設定時間だったらフラグをtrueにする
foreach ($users as $key => $user) {
  if ((strtotime($today) - strtotime($startdays[$key])) / 86400 % 28 < 21) {
    if ((date("H\n") == $settimes[$key] || date("G\n") == $settimes[$key]) && $takenok == 0) {
      $buf .= "1\n";
    } else {
      $buf .= $takens[$key];
    }
  } else {
    $buf .= $takens[$key];
  }
  // ファイルに保存
  file_put_contents('taken.lst.wk', $buf);
  // リネーム
  exec('mv -f taken.lst.wk taken.lst');
}

// 薬を飲んでいないユーザーにプッシュ
$takens = file('taken.lst');
foreach ($takens as $key => $taken) {
  if ($taken == "1\n" && $takenok == 0) {
    // POSTデータを設定してJSONにエンコード
    $post = [
      'to' => $users[$key],
      'messages' => [
        [
          'type' => 'text',
          'text' => "くすり飲んだー？",
        ],
      ],
    ];
    $post = json_encode($post);

    // HTTPリクエストを設定
    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    $options = [
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_BINARYTRANSFER => true,
      CURLOPT_HEADER => true,
      CURLOPT_POSTFIELDS => $post,
    ];
    curl_setopt_array($ch, $options);

    // 実行
    $result = curl_exec($ch);
  }
}

?>
