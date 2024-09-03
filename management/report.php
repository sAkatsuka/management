<?php 
session_start(); 
require 'header.php'; 
require 'nav.php'; 

// アクセスレベルの確認
if (isset($_SESSION['users']) && $_SESSION['users']['access_level'] >= 2) {
    $pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');

    $currentDate = new DateTime();
    $currentMonth = $currentDate->format("n"); // 現在の月（数字）
    $currentYear = $currentDate->format("Y"); // 現在の年
    $currentMonthYear = $currentDate->format("Y-m");

    // 今月の作業報告を1週間ごとにグループ化して表示
    echo '<h1 class="center">作業報告（月ごと）</h1>';
    $startOfMonth = $currentDate->format('Y-m-01');
    $endOfMonth = $currentDate->format('Y-m-t');

    $sql = $pdo->prepare('
        SELECT wr.user, ws.site AS site, wr.work, wr.created_at 
        FROM work_report wr
        JOIN work_site ws ON wr.site = ws.siteid
        JOIN work_details wd ON wr.workid = wd.workid
        WHERE wr.created_at BETWEEN ? AND ? 
        ORDER BY wr.created_at');
    $sql->execute([$startOfMonth, $endOfMonth]);
    $reports = $sql->fetchAll(PDO::FETCH_ASSOC);

    $weekStartDate = new DateTime($startOfMonth);
    $weekStartDate->modify('this week'); // 月の最初の週の開始日

    $weeks = [];
    foreach ($reports as $report) {
        $reportDate = new DateTime($report['created_at']);
        $reportDate->setTime(0, 0); // 時間部分を0に設定
        $weekNumber = (int)(($reportDate->format('j') + $weekStartDate->format('w')) / 7) + 1; // 週番号の計算
        $weekLabel = "{$currentMonth}月第{$weekNumber}週";

        if (!isset($weeks[$weekLabel])) {
            $weeks[$weekLabel] = [];
        }
        $weeks[$weekLabel][] = $report;
    }

    foreach ($weeks as $weekLabel => $weekReports) {
        echo '<div class="center">';
        echo "<h2>{$weekLabel}</h2>";
        echo '</div>';
        echo '<table border="1" class="table_a">';
        echo '<tr><th>作業者名</th><th>現場名</th><th>作業内容</th><th>作成日</th></tr>';
        foreach ($weekReports as $report) {
            $reportDate = new DateTime($report['created_at']);
            $reportDate->setTime(0, 0); // 時間部分を0に設定
            echo '<tr>';
            echo '<td>' . htmlspecialchars($report['user'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($report['site'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($report['work'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($reportDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') . '</td>'; // 日付のみ表示
            echo '</tr>';
        }
        echo '</table>';
    }

    // 1ヶ月以上前の作業報告を非表示にし、ボタンで表示
    echo '<h2 class="center">過去の報告（月ごと）</h2>';

    $sql = $pdo->prepare('
        SELECT wr.user, ws.site AS site, wr.work, wr.created_at 
        FROM work_report wr
        JOIN work_site ws ON wr.site = ws.siteid
        WHERE wr.created_at < ? 
        ORDER BY wr.created_at');
    $sql->execute([$startOfMonth]);
    $oldReports = $sql->fetchAll(PDO::FETCH_ASSOC);

    $monthGroups = [];
    foreach ($oldReports as $report) {
        $reportDate = new DateTime($report['created_at']);
        $reportMonth = $reportDate->format("n");
        $reportYear = $reportDate->format("Y");

        if (!isset($monthGroups[$reportYear][$reportMonth])) {
            $monthGroups[$reportYear][$reportMonth] = [];
        }
        $monthGroups[$reportYear][$reportMonth][] = $report;
    }

    foreach ($monthGroups as $year => $months) {
        foreach ($months as $month => $reports) {
            echo '<div class="center_a">';
            echo "<button onclick=\"document.getElementById('month_$year-$month').style.display='block'\">{$year}年{$month}月の報告を表示</button>";
            echo '</div>';
            echo "<div id=\"month_$year-$month\" style=\"display:none;\">";
            echo '<div class="center">';
            echo "<h3>{$year}年{$month}月の報告</h3>";
            echo '</div>';
            echo '<table border="1" class="table_a">';
            echo '<tr><th>作業者名</th><th>現場名</th><th>作業内容</th><th>作成日</th></tr>';
            foreach ($reports as $report) {
                $reportDate = new DateTime($report['created_at']);
                $reportDate->setTime(0, 0); // 時間部分を0に設定
                echo '<tr>';
                echo '<td>' . htmlspecialchars($report['user'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($report['site'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($report['work'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($reportDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') . '</td>'; // 日付のみ表示
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }
    }
    echo '<div class="center">';
    echo '<a href="#">トップへ戻る</a>';
    echo '</div>';

    // 現場名で検索するフォームを追加
    echo '<form method="get" action="" class="center">';
    echo '<input type="text" name="site_search" placeholder="現場名を検索">　';
    echo '<input type="submit" value="検索" class="searchbottom">';
    echo '</form>';

    if (isset($_GET['site_search'])) {
        $siteSearch = $_GET['site_search'];

        $sql = $pdo->prepare('
            SELECT wr.user, ws.site AS site, wr.work, wr.created_at 
            FROM work_report wr
            JOIN work_site ws ON wr.site = ws.siteid
            WHERE ws.site LIKE ? 
            ORDER BY wr.created_at DESC');
        $sql->execute(['%' . $siteSearch . '%']);
        $searchResults = $sql->fetchAll(PDO::FETCH_ASSOC);

        echo '<h2 class="center">検索結果</h2>';
        echo '<table border="1"  class="table_a">';
        echo '<tr><th>作業者名</th><th>現場名</th><th>作業内容</th><th>作成日</th></tr>';
        foreach ($searchResults as $result) {
            $resultDate = new DateTime($result['created_at']);
            $resultDate->setTime(0, 0); // 時間部分を0に設定
            echo '<tr>';
            echo '<td>' . htmlspecialchars($result['user'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($result['site'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($result['work'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($resultDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') . '</td>'; // 日付のみ表示
            echo '</tr>';
        }
        echo '</table>';
        echo '<div class="center">';
        echo '<a href="#">トップへ戻る</a>';
        echo '</div>';
    }

} else {
    echo '<p class="message">アクセス権がありません。</p>';
}

require 'footer.php';
?>
