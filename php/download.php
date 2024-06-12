<?php
    include("secret.php");

    error_reporting(E_ALL);
    ini_set('display_errors', 'On');

    // Specify the date
    $date = $_GET["date"] ?? "";
    if (strlen($date) == 0) {
        echo 'No date supplied.';
        exit;
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
        echo 'Provided date should be YYYY-mm-dd.';
        exit;
    }

    // Get the image
    $website = 'https://www.gocomics.com/pearlsbeforeswine/'.date('Y/m/d', strtotime($date));
    preg_match("/data-image=\"(https:\/\/assets.amuniversal.com\/.*?)\"/", file_get_contents($website), $matches);
    if (!array_key_exists(1, $matches)) {
        echo 'Failed to find comic.';
        exit;
    }
    $comic_url = $matches[1];

    // Insert it into the SQL database
    $PDO = createConnection();

    // id, ocr, ocr_updated ignored
    $stmt = $PDO->prepare("INSERT IGNORE INTO strips(`url`, `small_url`, `date`) VALUES (:url, :small, :date)");
    $stmt->bindValue(":url", $comic_url, PDO::PARAM_STR);
    $stmt->bindValue(":small", $comic_url, PDO::PARAM_STR);
    $stmt->bindValue(":date", date('Y-m-d', strtotime($date)), PDO::PARAM_STR);
    $stmt->execute();

    // Hurrah!
    echo 'Updated ' . date('Y/m/d', strtotime($date)) . '.';
?>