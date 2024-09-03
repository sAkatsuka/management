<?php 
session_start(); 
require 'header.php'; 
require 'nav.php'; 

// アクセスレベルの確認
if (isset($_SESSION['users']) && $_SESSION['users']['access_level'] >= 2) {
    $pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');

    // 検索条件に基づいてクエリを動的に生成
    $sql = "SELECT users.name AS user, work_site.site AS site, DATE(work_report.created_at) AS date 
            FROM work_report 
            JOIN users ON work_report.user = users.name 
            JOIN work_site ON work_report.site = work_site.siteid";
    $conditions = [];
    $params = [];

    if (isset($_GET['name']) && $_GET['name'] != '') {
        $conditions[] = "users.name LIKE :name";
        $params[':name'] = '%' . $_GET['name'] . '%';
    }

    if (isset($_GET['site']) && $_GET['site'] != '') {
        $conditions[] = "work_site.site LIKE :site";
        $params[':site'] = '%' . $_GET['site'] . '%';
    }

    if ($conditions) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY users.name, work_report.created_at DESC";
    $sth = $pdo->prepare($sql);
    $sth->execute($params);

    echo '<form method="get" class="search_a">';
    echo '<div>検索したい社員名を入力してください</div>';
    echo '<br>';
    echo '氏名<input type="text" name="name">';
    echo '<p><button type="submit" class="bottom">検索</button></p>';
    echo '</form>';

    echo '<form method="get" class="search_a">';
    echo '<div>検索したい現場名を入力してください</div>';
    echo '<br>';
    echo '現場<input type="text" name="site">';
    echo '<p><button type="submit" class="bottom">検索</button></p>';
    echo '</form>';

    // 検索結果の表示
    echo '<table border="1" class="table">';
    echo '<tr><th>氏名</th><th>現場名</th><th>日付</th></tr>';
    $lastUser = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if ($row['user'] !== $lastUser) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['user'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['site'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
            $lastUser = $row['user'];
        }
    }
    echo '</table>';
    echo '<div class="center">';
    echo '<a href="#">トップへ戻る</a>';
    echo '</div>';

    // 全ユーザーと最新の現場名を表示
    $sql = "SELECT users.name AS user, 
                    (SELECT work_site.site 
                    FROM work_report 
                    JOIN work_site ON work_report.site = work_site.siteid 
                    WHERE work_report.user = users.name 
                    ORDER BY work_report.created_at DESC LIMIT 1) AS latest_site 
            FROM users";
    $sth = $pdo->prepare($sql);
    $sth->execute();

    echo '<h2 class="center">登録社員情報</h2>';
    echo '<table border="1" class="table">';
    echo '<tr><th>氏名</th><th>現場名</th></tr>';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['latest_site'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<div class="center">';
    echo '<a href="#">トップへ戻る</a>';
    echo '</div>';

} else {
    echo '<p class="center">アクセス権がありません。</p>';
}

require 'footer.php';
?>
