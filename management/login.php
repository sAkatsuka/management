<?php
session_start(); 

// データベース接続設定
try {
    $pdo = new PDO('mysql:host=mysql310.phy.lolipop.lan;dbname=LAA1621131-works;charset=utf8', 'LAA1621131', '3776jin');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo '<p class="center">データベース接続エラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// ログインセッションが設定されている場合はログアウト処理
if (isset($_SESSION['users'])) {
    unset($_SESSION['users']);
    echo '<p class="center">ログアウトしました。</p>';
}

// POSTリクエストがあった場合のログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    // ユーザーのデータを取得
    $sql = $pdo->prepare('SELECT * FROM users WHERE name = ? AND pass = ?');
    $sql->execute([$login, $password]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // セッションにユーザー情報を保存
        $_SESSION['users'] = [
            'userid' => $row['userid'],
            'name' => $row['name'],
            'access_level' => $row['access_level']
        ];        
        header('Location: main.php');
        exit;
    } else {
        echo '<p class="center">ログイン名またはパスワードが違います。</p>';
    }
}

require 'header.php';
require 'nav.php'; 

// ログイン画面の表示
if (!isset($_SESSION['users'])) {
    echo '<form action="login.php" method="post">';
    echo '<div class="center">';
    echo '<p class="login">ログイン名　<input type="text" name="login" required></p>';
    echo '<p class="login">パスワード　<input type="password" name="password" required id="password"> <button type="button" id="togglePassword">表示</button></p>';
    echo '<p class="login"><button type="submit">ログイン</button></p>';
    echo '</div>';
    echo '</form>';
}

require 'footer.php'; 
?>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const buttonText = this.innerText === '表示' ? '非表示' : '表示';
    this.innerText = buttonText;
    passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
});
</script>
