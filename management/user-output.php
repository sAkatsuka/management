<?php
session_start(); 
require 'header.php'; 
require 'nav.php'; 

// データベース接続設定
$pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');

// // アクセスレベルの確認
if (isset($_SESSION['users']) && $_SESSION['users']['access_level'] >= 2) {
    $pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_name = isset($_POST['current_name']) ? trim($_POST['current_name']) : '';
    $current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
    $new_name = isset($_POST['new_name']) ? trim($_POST['new_name']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $access_level = isset($_POST['access_level']) ? trim($_POST['access_level']) : '';
    $register_name = isset($_POST['register_name']) ? trim($_POST['register_name']) : '';
    $register_password = isset($_POST['register_password']) ? trim($_POST['register_password']) : '';
    $register_access_level = isset($_POST['register_access_level']) ? trim($_POST['register_access_level']) : '';
    $action = $_POST['action'];

    if ($action === 'update') {
        // ユーザー情報の更新
        $sql = $pdo->prepare('SELECT pass FROM users WHERE name = ?');
        $sql->execute([$current_name]);
        $user = $sql->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['pass'] === $current_password) {
            // パスワードが正しい場合、名前、パスワード、アクセスレベルを更新
            $sql = $pdo->prepare('UPDATE users SET name = ?, pass = ?, access_level = ? WHERE name = ?');
            $sql->execute([$new_name, $password, $access_level, $current_name]);
            echo '<p>ユーザー情報が更新されました。</p>';
            echo '<a href="user-input.php">トップへ戻る</a>';
        } else {
            echo '<p>現在のパスワードが間違っています。</p>';
            echo '<a href="user-input.php">トップへ戻る</a>';
        }
    } elseif ($action === 'delete') {
        // ユーザーの削除
        $sql = $pdo->prepare('SELECT pass FROM users WHERE name = ?');
        $sql->execute([$current_name]);
        $user = $sql->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['pass'] === $current_password) {
            $sql = $pdo->prepare('DELETE FROM users WHERE name = ?');
            $sql->execute([$current_name]);
            echo '<p>ユーザーが削除されました。</p>';
        } else {
            echo '<p>現在のパスワードが間違っています。</p>';
            echo '<a href="user-input.php">トップへ戻る</a>';

        }
    } elseif ($action === 'register') {
        // 新規ユーザーの登録
        $sql = $pdo->prepare('INSERT INTO users (name, pass, access_level) VALUES (?, ?, ?)');
        $sql->execute([$register_name, $register_password, $register_access_level]);
        echo '<p>新規ユーザーが登録されました。</p>';
        echo '<a href="user-input.php">トップへ戻る</a>';
    }
}

} else {
    echo '<p>アクセス権がありません。</p>';
}


require 'footer.php'; 
?>
