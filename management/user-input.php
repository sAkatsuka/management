<?php
session_start(); 
require 'header.php'; 
require 'nav.php'; 

// // アクセスレベルの確認
if (isset($_SESSION['users']) && $_SESSION['users']['access_level'] >= 2) {
    $pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');

echo '<h1 class="center">ユーザー情報の更新/削除/新規登録</h1>';

// ユーザーを検索するためのフォーム
echo '<h2 class="center">ユーザー検索</h2>';
echo '<form action="user-input.php" method="post" class="center">';
echo '検索する氏名　<input type="text" name="name" required class="search">';
echo '<p><button type="submit" name="action" class="bottom" value="search">検索</button></p>';
echo '</form>';

// 検索結果がある場合は、更新/削除フォームを表示
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $name = trim($_POST['name']);

    // データベース接続設定
    $pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');

    // ユーザーのデータを取得
    $sql = $pdo->prepare('SELECT * FROM users WHERE name = ?');
    $sql->execute([$name]);
    $user = $sql->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // ユーザー情報が見つかった場合、更新/削除フォームを表示
        echo '<h2 class="center">ユーザー情報の更新</h2>';
        echo '<form action="user-output.php" method="post" class="update_form">';
        echo '現在の氏名<input type="text" name="current_name" class="update" value="' . htmlspecialchars($user['name']) . '" readonly>';
        echo '　現在のパスワード<input type="password" name="current_password" class="update" required>';
        echo '<br>';
        echo '新しい氏名<input type="text" name="new_name" class="update" value="' . htmlspecialchars($user['name']) . '">';
        echo '　新しいパスワード<input type="password" name="password" class="update" required>';
        echo '　アクセスレベル<input type="number" name="access_level" class="update" value="' . htmlspecialchars($user['access_level']) . '" required>';
        echo '<br>';
        echo '更新したい場合は氏名、パスワード、アクセスレベルを入力し更新ボタンを押してください。';
        echo '※同じ情報を入力しても大丈夫です。';
        echo '<p><button type="submit" name="action" value="update" class="bottom">更新</button></p>';
        echo '<br>';
        echo '検索した人物を削除したい場合は削除ボタンを押してください。';
        echo '<p><button type="submit" name="action" value="delete" class="bottom" onclick="return confirm(\'本当に削除しますか?\')">削除</button></p>';
        echo '</form>';
        echo '<div class="center">';
        echo '<a href="#">トップへ戻る</a>';
        echo '</div>';
    
    } else {
        // ユーザーが見つからない場合のメッセージ
        echo '<p class="message">ユーザーが見つかりませんでした。</p>';
    }
}

// 新規登録フォームの表示
echo '<h2 class="center">ユーザーの新規登録</h2>';
echo '<form action="user-output.php" method="post" class="center">';
echo '<div class="new">';
echo '氏名　<input type="text" name="register_name" required class="newr">';
echo 'パスワード　<input type="password" name="register_password" required class="newr">';
echo 'アクセスレベル　<input type="number" name="register_access_level" required class="newr">';
echo '<p><button type="submit" name="action" value="register" class="newbottom">新規登録</button></p>';
echo '<div>';
echo '</form>';

} else {
    echo '<p class="center">アクセス権がありません。</p>';
}

require 'footer.php'; 
?>
