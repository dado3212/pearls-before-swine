<?php
	// Fetch from PHP and just inline it for JS
	include("php/secret.php");

	error_reporting(-1);
	ini_set('display_errors', 'On');

	$limit = 25;

	if (isset($_GET["page"]) && is_numeric($_GET["page"])) {
		$page = intval($_GET["page"]);
	} else {
		$page = 0;
	}

	if (isset($_GET["q"]) && strlen($_GET["q"]) > 0) {
		$query = $_GET["q"];
	} else {
		$query = "";
	}

	$PDO = createConnection();

	function tokenize($string) {
		// Use preg_match_all to find all quoted and unquoted strings
		preg_match_all('/"([^"]+)"|(\S+)/', $string, $matches);
		$tokens = [];
		
		// Extract the matches and store them in an array
		foreach ($matches[0] as $match) {
			// Remove surrounding quotes if present
			$tokens[] = trim($match, '"');
		}
		
		return $tokens;
	}

	if ($query !== "") {
		$words = tokenize($query);

		$queryString = "SELECT * FROM strips WHERE ";
		foreach ($words as $word) {
			$queryString .= "ocr LIKE CONCAT('%', ?, '%') AND ";
		}
		$queryString = substr($queryString, 0, -4);
		$queryString .= "ORDER BY date ASC LIMIT $limit OFFSET " . $page * $limit;

		$stmt = $PDO->prepare($queryString);
		for ($i = 0; $i < count($words); $i++) {
			$stmt->bindParam($i + 1, $words[$i]);
		}
	} else {
		$stmt = $PDO->prepare("SELECT * FROM strips ORDER BY date DESC LIMIT $limit OFFSET " . $page * $limit);
	}
	
	$stmt->execute();

	$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Pearls Before Swine | Search</title>

		<!-- Meta tags -->
        <meta name="robots" content="index, follow, archive">
        <meta name="description" content="Search all historical Pearls Before Swine comic strips by text!">
        <meta charset="utf-8" />
        <meta http-equiv="Cache-control" content="public">

        <!-- Semantic Markup -->
        <meta property="og:title" content="Pearls Before Swine | Search">
        <meta property="og:type" content="website">
        <meta property="og:image" content="https://alexbeals.com/projects/pearls/assets/preview.png">
        <meta property="og:url" content="https://alexbeals.com/projects/pearls">
        <meta property="og:description" content="Search all historical Pearls Before Swine comic strips by text!">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:creator" content="@alex_beals">

		<!-- Favicons -->
		<link rel="apple-touch-icon" sizes="180x180" href="./assets/favicon/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png">
		<link rel="manifest" href="./assets/favicon/site.webmanifest">
		<link rel="mask-icon" href="./assets/favicon/safari-pinned-tab.svg" color="#5bbad5">
		<link rel="shortcut icon" href="./assets/favicon/favicon.ico">
		<meta name="msapplication-TileColor" content="#da532c">
		<meta name="msapplication-config" content="./assets/favicon/browserconfig.xml">
		<meta name="theme-color" content="#ffffff">

		<link rel="stylesheet" type="text/css" href="assets/main.css">
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-M15SP790QM"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-M15SP790QM');
        </script>
		<script>
			function blowUp(bigImage) {
				if (event.altKey) {
					window.open(bigImage, '_blank');
				}
			}
		</script>

		<?php
			// Respects 'Request Desktop Site'
			if (preg_match("/(iPhone|iPod|iPad|Android|BlackBerry)/i", $_SERVER['HTTP_USER_AGENT'])) {
				?><meta name="viewport" content="width=device-width, initial-scale=1.0"><?php
			}
		?>
	</head>
	<body>
		<header>
			<h1>
				<img src="./assets/pig.png">
				<a href="/projects/pearls">Pearls Before Swine | Search</a>
			</h1>
			<h3>Unaffiliated with Pearls Before Swine</h3>
		</header>

		<form action="/projects/pearls">
			<input type="search" results=3 name="q" placeholder="Search keyword(s)..." <?php if ($query !== "") { echo "value='{$query}'"; } ?> />
			<button type="submit">Search</button>
		</form>

		<?php
			function highlightWords($text, $words) {
				$output = '';
				$length = strlen($text);
				$i = 0;
			
				while ($i < $length) {
					$matched = false;
			
					// Check each word in the list
					foreach ($words as $word) {
						$wordLength = strlen($word);
			
						// Check if the word matches the substring starting at the current position
						if (strncasecmp(substr($text, $i, $wordLength), $word, $wordLength) == 0) {
							$output .= "<span class='found'>" . substr($text, $i, $wordLength) . "</span>";
							$i += $wordLength;
							$matched = true;
							break;
						}
					}
			
					// If no match, add the current character to the output
					if (!$matched) {
						$output .= $text[$i];
						$i++;
					}
				}
			
				return $output;
			}
			
			foreach ($comics as $strip) {
				if ($strip["ocr"] === null) {
					$ocr_text = 'OCR failed to run. Please file an issue in <a href="https://github.com/dado3212/pearls-before-swine/issues/" target="_blank">https://github.com/dado3212/pearls-before-swine/issues/</a>';
				} else {
					$ocr_text = htmlspecialchars($strip["ocr"]);
					if ($query !== "") {
						$words = tokenize($query);
						$ocr_text = highlightWords($ocr_text, $words);
						$ocr_text = preg_replace("/\n/", "<br>", preg_replace("/\n\n/", "\n", $ocr_text));
					} else {
						$ocr_text = preg_replace("/\n/", "<br>", preg_replace("/\n\n/", "\n", $ocr_text));
					}
				}

				echo "
					<div class='comic'>
						<div class='title'><span>" . date('F j, Y', strtotime($strip['date'])) . "</span><a href=\"https://www.gocomics.com/pearlsbeforeswine/".date('Y/m/d', strtotime($strip['date']))."\" target=\"_blank\">⋐⋑</a></div>
						<div class='main'>
							<div class='image'>
								<img src='{$strip['small_url']}' onclick='blowUp(\"{$strip['url']}\");' />
							</div>
							<p class='ocr'>
								{$ocr_text}
							</p>
						</div>
					</div>
				";
			}

			echo "<div class='navigation'>";

			if ($query !== "") {
				$queryString = "?q=" . urlencode($query);
			} else {
				$queryString = "";
			}

			// If you're not on page 0, you can go back
			if ($page > 0) {
				echo "<a href='/projects/pearls/page/" . ($page - 1) . "$queryString'>← Previous</a>";
			}
			// If you hit the limit, there are (probably?) more.
			if (count($comics) === $limit) {
				echo "<a href='/projects/pearls/page/" . ($page + 1) . "$queryString'>Next →</a>";
			}
		?>
		</div>
	</body>
</html>