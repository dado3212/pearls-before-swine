# Pearls

## One time setup
From https://www.reddit.com/prefs/apps, create a new app and add the Client ID and Client Secret to the secret.php file. Add in the reddit username and password as well between secret.php and reddit.php.

## Crontab
This triggers at 8am PST every day (server is at UTC)
```
00 15 * * * php /var/www/alexbeals.com/public_html/projects/pearls/php/download.php <dl_code>
```

## Backfilling Comics
To download just the images, without the OCR you'll want to do this, and uncomment the OCR code.
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

For backfilling OCR if you check out commit 291f234 then you can use `lmao.php` and `updateOCR.php` to bulk-process these.
```
https://alexbeals.com/projects/pearls/php/lmao.php?q=<code>
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

define('REDDIT_CLIENT_ID', '<code4>');
define('REDDIT_CLIENT_SECRET', '<code5>');
define('REDDIT_PASSWORD', '<code6>');

?>
```