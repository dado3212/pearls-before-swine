# <img src="/assets/pig.png" width="40" alt="Logo"/> Pearls Before Swine

This repository does a couple of things around the webcomic [Pearls Before Swine](https://www.gocomics.com/pearlsbeforeswine) (all credits go to its creator/character Stephen Pastis). 

For one, it's the code of the indexer which has a transcription for every strip, and is searchable on https://alexbeals.com/projects/pearls/. This can help to find comics matching themes, or just finding one that you half-remember.

<img src="/assets/preview.png?raw=true" height="300" alt="Preview"/>

It also has the code for automatically downloading the daily Pearls Before Swine, running it through OCR analysis (what used to be a modified version of Tesseract, and is now politely asking ChatGPT), adds it to the aforementioned indexer, and posts it to https://www.reddit.com/r/pearlsbeforeswine.

If you're interested in the transcribed data, a dump of the SQL database as of June 13th, 2024 is in the assets folder.

## Setup

### Create Reddit App
From https://www.reddit.com/prefs/apps, create a new app. Mark down the Client ID and Client Secret, it will be needed in the next step. You will also need to modify `reddit.php` to use your reddit username.

### Create `secret.php` file.

Add a file to the `php` folder called `secret.php` with this syntax. Fill in the <> bracketed fields.

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

### Crontab

Automatically trigger this daily. I do this with crontab, triggering at 8am PST every day (server is on UTC).
```
00 15 * * * php /var/www/alexbeals.com/public_html/projects/pearls/php/download.php <DOWNLOAD_CODE>
```

## Backfilling Comics
To download just the images, you'll want comment out the OCR and Reddit code and run this:

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

For backfilling OCR if you check out commit 291f234 then you can use the descriptively named `lmao.php` and `updateOCR.php` to bulk-process these.
```
https://alexbeals.com/projects/pearls/php/lmao.php?q=<code>
```

