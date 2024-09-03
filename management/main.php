<?php 
session_start(); 
require 'header.php'; 
require 'nav.php'; 

$pdo = new PDO('mysql:host=localhost;dbname=work;charset=utf8', 'root', '');

// アクセスレベルの確認
if (isset($_SESSION['users']) && $_SESSION['users']['access_level'] >= 0) {
    $userid = $_SESSION['users']['userid'];
    $name = $_SESSION['users']['name'];

    $sql = $pdo->prepare('SELECT name FROM users');
    $sql->execute();
    $users = $sql->fetchAll(PDO::FETCH_ASSOC);

    $sql = $pdo->prepare('SELECT work, workid FROM work_details');
    $sql->execute();
    $workDetails = $sql->fetchAll(PDO::FETCH_ASSOC);

    $workidMap = [];
    foreach ($workDetails as $detail) {
        $workidMap[$detail['work']] = $detail['workid'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $invalidNames = [];

    // 入力された作業者名が users テーブルに登録されているか確認
    foreach ($_POST['worker_name'] as $worker_name) {
        $worker_name = trim($worker_name);
        $sql = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ?');
        $sql->execute([$worker_name]);
        $count = $sql->fetchColumn();

        if ($count == 0) {
            $invalidNames[] = $worker_name;
        }
    }

// 登録されていない名前があればエラーメッセージを表示
if (!empty($invalidNames)) {
    echo '<div class="center">';
    foreach ($invalidNames as $invalidName) {
        echo '<p class="error">「' . htmlspecialchars($invalidName, ENT_QUOTES, 'UTF-8') . '」は登録されていません。</p>';
    }
    echo '<a href="main.php">トップへ戻る</a>';
    echo '</div>';
    exit;
}

        $site = trim($_POST['site']);

        // 現場名が既に登録されているか確認し、登録されていない場合は追加する
        $sql = $pdo->prepare('SELECT siteid FROM work_site WHERE site = ?');
        $sql->execute([$site]);
        $siteid = $sql->fetchColumn();

        if (!$siteid) {
            $sql = $pdo->prepare('INSERT INTO work_site (site) VALUES (?)');
            $sql->execute([$site]);
            $siteid = $pdo->lastInsertId();
        }

        for ($i = 0; $i < count($_POST['worker_name']); $i++) {
            $worker_name = trim($_POST['worker_name'][$i]);
            $work = trim($_POST['work'][$i]);
            $other_work = trim($_POST['other_work'][$i]);

            if (!empty($other_work)) {
                // その他の作業内容が入力された場合、それを作業内容として保存する
                if (!array_key_exists($other_work, $workidMap)) {
                    $sql = $pdo->prepare('INSERT INTO work_details (work) VALUES (?)');
                    $sql->execute([$other_work]);
                    $workid = $pdo->lastInsertId();
                    $workidMap[$other_work] = $workid;
                } else {
                    $workid = $workidMap[$other_work];
                }
                $work = $other_work; // その他の作業内容をworkに代入
            } else {
                if (!empty($work) && array_key_exists($work, $workidMap)) {
                    $workid = $workidMap[$work];
                } else {
                    continue;
                }
            }

            $currentDate = new DateTime();
            $weekNumber = $currentDate->format("W");
            $monthYear = $currentDate->format("Y-m");

            // 現場名と作業内容が存在するか確認し、存在しない場合は追加する
            $sql = $pdo->prepare('SELECT * FROM work_report WHERE user = ? AND site = ? AND workid = ?');
            $sql->execute([$worker_name, $siteid, $workid]);
            $existing = $sql->fetch();

            if ($existing) {
                // 既存の作業報告がある場合、作業内容を更新
                $sql = $pdo->prepare('UPDATE work_report SET work = ?, week_number = ?, month_year = ?, created_at = NOW() WHERE user = ? AND site = ? AND workid = ?');
                $sql->execute([$work, $weekNumber, $monthYear, $worker_name, $siteid, $workid]);
            } else {
                // 新しい作業報告を追加
                $sql = $pdo->prepare('INSERT INTO work_report (user, site, workid, work, week_number, month_year, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $sql->execute([$worker_name, $siteid, $workid, $work, $weekNumber, $monthYear]);
            }
        }

        echo '<div class="center">';
        echo '<p class="message">送信が完了しました。</p>';
        echo '<a href="main.php">トップへ戻る</a>';
        echo '</div>';

    } else {
        ?>
        <h1 class="center">作業報告フォーム</h1>
        <form action="main.php" method="post" class="main">
            <div>
                <label for="site">現場名:</label>
                <input type="text" name="site" id="site" class="work_form" required>
                <p>※作業内容の欄かその他の欄に作業内容を入力してください。</p>
                <p>※すでに登録されている作業者は作業者名を、作業内容は作業内容欄を選択すると一覧が出てきます。</p>
            </div>
            <div id="workers">
                <div class="worker">
                    <label for="worker_name_0">作業者名:</label>
                    <input type="text" name="worker_name[]" id="worker_name_0" class="work_form" list="user-list" required>
                    <label for="work_0">作業内容:</label>
                    <input type="text" name="work[]" id="work_0" class="work_form"  list="work-list">
                    <label for="other_work_0">その他の作業内容:</label>
                    <input type="text" name="other_work[]" id="other_work_0" class="work_form">
                    <button type="button" class="remove-worker">作業者を削除</button>
                </div>
            </div>
            <button type="button" id="add-worker" class="work_form">作業者を追加</button>
            <button type="submit" class="work_form">送信</button>
        </form>

        <datalist id="user-list">
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <datalist id="work-list">
            <?php foreach ($workDetails as $detail): ?>
                <option value="<?= htmlspecialchars($detail['work'], ENT_QUOTES, 'UTF-8') ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <script>
document.getElementById('add-worker').addEventListener('click', function() {
    const workerCount = document.querySelectorAll('.worker').length;
    const workerDiv = document.createElement('div');
    workerDiv.classList.add('worker');

    const workerLabel = document.createElement('label');
    workerLabel.setAttribute('for', 'worker_name_' + workerCount);
    workerLabel.textContent = '作業者名:';
    workerDiv.appendChild(workerLabel);

    const workerInput = document.createElement('input');
    workerInput.setAttribute('type', 'text');
    workerInput.setAttribute('name', 'worker_name[]');
    workerInput.setAttribute('id', 'worker_name_' + workerCount);
    workerInput.setAttribute('list', 'user-list');
    workerInput.classList.add('work_form'); // クラスを追加
    workerInput.required = true;
    workerDiv.appendChild(workerInput);

    const workLabel = document.createElement('label');
    workLabel.setAttribute('for', 'work_' + workerCount);
    workLabel.textContent = '作業内容:';
    workerDiv.appendChild(workLabel);

    const workInput = document.createElement('input');
    workInput.setAttribute('type', 'text');
    workInput.setAttribute('name', 'work[]');
    workInput.setAttribute('id', 'work_' + workerCount);
    workInput.setAttribute('list', 'work-list');
    workInput.classList.add('work_form'); // クラスを追加
    workerDiv.appendChild(workInput);

    const otherWorkLabel = document.createElement('label');
    otherWorkLabel.setAttribute('for', 'other_work_' + workerCount);
    otherWorkLabel.textContent = 'その他の作業内容:';
    workerDiv.appendChild(otherWorkLabel);

    const otherWorkInput = document.createElement('input');
    otherWorkInput.setAttribute('type', 'text');
    otherWorkInput.setAttribute('name', 'other_work[]');
    otherWorkInput.setAttribute('id', 'other_work_' + workerCount);
    otherWorkInput.classList.add('work_form'); // クラスを追加
    workerDiv.appendChild(otherWorkInput);

    const removeButton = document.createElement('button');
    removeButton.setAttribute('type', 'button');
    removeButton.classList.add('remove-worker');
    removeButton.textContent = '作業者を削除';
    workerDiv.appendChild(removeButton);

    document.getElementById('workers').appendChild(workerDiv);
});

            document.getElementById('workers').addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-worker')) {
                    e.target.parentElement.remove();
                }
            });
        </script>
        <?php
    }
} else {
    echo '<div class="center">';
    echo '<p>ログインしてください。</p>';
    echo '<a href="login.php">ログイン</a>';
    echo '</div>';
}

require 'footer.php';
?>
