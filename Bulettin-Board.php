<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>Bulettin Board</title>
    </head>
    <body>
        <?php
            // タイムゾーン設定
            date_default_timezone_set('Asia/Tokyo');

            // MySQLのデータベースに接続
            $dsn = 'データベース名';
            $user = 'ユーザー名';
            $password = 'パスワード';

            $pdo = new PDO($dsn, $user, $password,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

            // なければテーブルを作成
            $sql = "CREATE TABLE IF NOT EXISTS keijiban"
            ."("
            ."id INT AUTO_INCREMENT PRIMARY KEY,"
            ."name char(32),"
            ."comment TEXT,"
            ."time TEXT,"
            ."password TEXT"
            .");";
            $stmt = $pdo -> query($sql);


            // 削除機能
            // もし削除ボタンが押されたら
            if (isset($_POST["delete"])) {
                $delno = $_POST["deleteNo"];
                $delPass = $_POST["delPass"];

                // なおかつ削除対象番号が空でない時
                if (isset($delno)) {
                    $sql = 'SELECT * FROM keijiban';
                    $stmt = $pdo -> query($sql);
                    $results = $stmt -> fetchAll();
                    foreach ($results as $row) {
                    // パスワードが一致したら
                        if ($row['id'] == $delno && $row["password"] == $delPass) {
                            $sql = 'delete from keijiban where id=:id';
                            $stmt = $pdo -> prepare($sql);
                            $stmt -> bindParam(':id', $delno, PDO::PARAM_INT);
                            $stmt -> execute();

                            echo "削除しました！" . "<br>";
                        } elseif ($row['id'] == $delno && $row["password"] !== $delPass) {
                            echo "※パスワードが違います！" . "<br>";
                        }
                    }
                }
            }

            // 編集機能
            // もし編集ボタンが押されたら
            if (isset($_POST["edit"])) {
                $editno = $_POST["editNo"];

                $sql = 'SELECT * FROM keijiban';
                $stmt = $pdo -> query($sql);
                $results = $stmt -> fetchAll();
                foreach ($results as $row) {
                    // rowの中にはテーブルのカラムが入る
                    if ($row["id"] == $editno) {
                        $edit_name = $row['name'];
                        $edit_comment = $row['comment'];
                        
                        // 編集番号保存
                        $fd = fopen("number.txt", "w");
                        flock($fd, LOCK_EX);
                        ftruncate($fd, 0);
                        rewind($fd);
                        fwrite($fd, $row["id"]);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                    }
                }
            }

            // 投稿機能
            // もし送信ボタンが押されたら
            if (isset($_POST["submit"])) {
                $name = $_POST["name"];
                $comment = $_POST["comment"];
                $time = date("Y/m/d H:i:s");
                $edPass = $_POST["password"];

                $en = file("number.txt");

                // 編集実行
                if (isset($en[0])) {
                    $sql = 'SELECT * FROM keijiban';
                    $stmt = $pdo -> query($sql);
                    $results = $stmt -> fetchAll();
                    foreach ($results as $row) {
                    // パスワードが一致したら
                        if ($row['id'] == $en[0] && $row["password"] == $edPass) {
                            $id = $en[0];

                            $sql = 'UPDATE keijiban SET name=:name, comment=:comment, time=:time, 
                                    password=:password WHERE id=:id';
                            $stmt = $pdo -> prepare($sql);
                            $stmt -> bindParam(':name', $name, PDO::PARAM_STR);
                            $stmt -> bindParam(':comment', $comment, PDO::PARAM_STR);
                            $stmt -> bindParam(':time', $time, PDO::PARAM_STR);
                            $stmt -> bindParam(':password', $row["password"], PDO::PARAM_STR);
                            $stmt -> bindParam(':id', $id, PDO::PARAM_STR);
                            $stmt -> execute();

                            echo "編集しました！" . "<br>";
                        } elseif ($row['id'] == $en[0] && $row["password"] !== $edPass) {
                            echo "※パスワードが違います！" . "<br>";
                        }
                    }

                    // 編集番号リセット
                    $fd = fopen("number.txt", "w");
                    flock($fd, LOCK_EX);
                    fwrite($fd, NULL);
                    flock($fd, LOCK_UN);
                    fclose($fd);

                // 新規投稿
                } else {
                    $edit_name = NULL; 
                    $edit_comment = NULL;
                    $pass = $_POST["password"];
                    // なおかつきちんと入力されている時
                    if (isset($name) && isset($comment)) {
                        // テーブルに書き込み
                        $sql = $pdo -> prepare("INSERT INTO keijiban (name, comment, time, password) 
                                            VALUES (:name, :comment, :time, :password)");
                        $sql -> bindParam(':name', $name, PDO::PARAM_STR);
                        $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
                        $sql -> bindParam(':time', $time, PDO::PARAM_STR);
                        $sql -> bindParam(':password', $pass, PDO::PARAM_STR);
                        $sql -> execute();
                    }
                }
            }

        ?>

        <!-- フォーム -->
        <form action="" method="post">
            <input type="text" name="name" placeholder="名前"
                    value="<?php
                                // フォームに編集対象を再表示
                                if (isset($edit_name)) {
                                    echo $edit_name;
                                }
                            ?>">
            <input type="text" name="comment" placeholder="コメント"
                    value="<?php
                                if (isset($edit_comment)) {
                                    echo $edit_comment;
                                }
                            ?>">
            <input type="text" name="password" placeholder="パスワード">
            <input type="submit" name="submit">
        </form>
        <form action="" method="post">
            削除対象番号<input type="int" name="deleteNo">
            <input type="text" name="delPass" placeholder="パスワード">
            <input type="submit" name="delete" value="削除">
        </form>
        <form action="" method="post">
            編集対象番号<input type="int" name="editNo">
            <input type="submit" name="edit" value="編集">
        </form>

        <br>

        <?php
            // 表示機能
            $sql = 'SELECT * FROM keijiban';
            $stmt = $pdo -> query($sql);
            $results = $stmt -> fetchAll();
            foreach ($results as $row) {
                // rowの中にはテーブルのカラムが入る
                echo $row['id'] . '. ';
                echo $row['name'] . ': ';
                echo $row['comment'] . '(';
                echo $row['time'] . ')<br>';
                echo "<hr>";
            }
        ?>
    </body>
</html>