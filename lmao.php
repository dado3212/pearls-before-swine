<?php

// Fetch from PHP and just inline it for JS
include("php/secret.php");

if (isset($_GET["q"]) && strlen($_GET["q"]) > 0) {
    $query = $_GET["q"];
} else {
    $query = "";
}
if ($query !== CODE) {
    echo "no no no!";
    exit;
}
?>
<html>
    <head>
        <script>
            var count = 0;

            function trigger() {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', './updateOCR.php?q=<?php echo CODE; ?>', true);

                document.getElementById('progress').innerHTML = 'loading';

                xhr.onreadystatechange = function (data) {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        console.log(data.target.response);
                        count += 1;
                        document.getElementById('progress').innerHTML = 'waiting';
                        document.getElementById('text').innerHTML = '' + count + ' runs completed.';

                        setTimeout(trigger, 8000);
                    }
                }
                xhr.send();
            }
            window.onload = () => {
                trigger();
            }
        </script>
    </head>
    <body>
        <div id="text">0</div>
        <div id="progress"></div>
    </body>
</html>