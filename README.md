# Pearls

## Backfilling Comics
```
let currentDate = new Date("2024-06-05");
let endDate = new Date("2024-06-14");
while (currentDate < endDate) {
    // Fetch the URL
    let url = await fetch('https://alexbeals.com/projects/pearls/php/download.php?dl=<code>&date=' + currentDate.toISOString().split('T')[0]);
    console.log(await url.text());

    // Increment the date
    currentDate.setDate(currentDate.getDate() + 1);
}
```

## Secret.php

Add a file to the `php` folder called `secret.php` with this syntax.

```
<?php
/**
 *  Creates a database connection to the classes database
 *
 *	@return: Set up PDO instance
 **/
function createConnection() {
    try {
        $PDO = new PDO("mysql:host=localhost;dbname=pearls;charset=utf8",'<username>','<password>');
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        echo "PDO MySQL Failed to connect: " . $e->getMessage();
    }

    return $PDO;
}

define('CODE', '<code>');
define('DOWNLOAD_CODE', '<code2>');
define('OPEN_AI_TOKEN', '<code3>');

?>
```