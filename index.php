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

	if ($query !== "") {
		$words = explode(" ", $query);

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
		$stmt = $PDO->prepare("SELECT * FROM strips ORDER BY date ASC LIMIT $limit OFFSET " . $page * $limit);
	}
	
	$stmt->execute();

	$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Pearls Before Swine Index</title>

		<?php
			// Respects 'Request Desktop Site'
			if (preg_match("/(iPhone|iPod|iPad|Android|BlackBerry)/i", $_SERVER['HTTP_USER_AGENT'])) {
				?><meta name="viewport" content="width=900"><?php
			}
		?>

		<style>
			html, body {
				background-color: #f7f7f7;
				color: #484c55;
				min-height: 100%;

				font-size: 14px;
				font-family: sans-serif;
			}

			body {
				max-width: 75em;
				margin: 0 auto;
			}

			header {
				text-align: center;
			}

			header a {
				display: table;
				margin: 0 auto;
				text-decoration: none;
				color: #484c55;
			}

			form {
				display: flex;
				margin-bottom: 10px;
			}

			form * {
				-webkit-appearance: none;
				font-size: 1.2em;
				background-color: white;
				border: 1px solid gray;
				border-radius: 0px;
			}

			input {
				flex: 1;
				border-top-left-radius: 5px;
				border-bottom-left-radius: 5px;
				padding-left: 10px;
			}

			button {
				padding: 10px;
				border-top-right-radius: 5px;
				border-bottom-right-radius: 5px;
			}

			.comic {
				margin-bottom: 15px;
				background-color: white;
				padding: 10px;
				border-radius: 6px;
				box-shadow: 0 0 3px rgba(0, 0, 0, 0.15);
			}

			.comic > div {
				display: flex;
			}

			.comic .image {
				min-width: 600px;
			}

			.comic img {
				max-width: 100%;
			}

			.comic p {
				margin: 0 0 0 10px;
			}

			.comic p .found {
				background-color: #ffff00;
			}

			.comic .title {
				font-weight: bold;
				font-family: sans-serif;
				font-size: 1.4em;
			}

			.comic .title a {
				/** Nothing works to style it not as emoji :( */
				padding-left: 5px;
    			text-decoration: none;
			}

			.navigation {
				text-align: center;
				padding-bottom: 20px;
			}

			.navigation a {
				width: 6em;
				margin: 5px;
				display: inline-block;
				padding: 10px;
				border-radius: 6px;
				background-color: white;
				color: #484c55;
				text-decoration: none;

				box-shadow: 0 0 3px rgba(0, 0, 0, 0.15);

				transition: all 0.2s ease;
			}

			.navigation a:hover {
				box-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
			}
		</style>
	</head>
	<body>
		<header>
			<a href="/projects/pearls">
				<h1>Pearls Before Swine Indexer</h1>
				<h3>Unaffiliated with Pearls Before Swine</h3>
			</a>
		</header>

		<form action="/projects/pearls">
			<input type="search" results=3 name="q" placeholder="Search keyword(s)..." <?php if ($query !== "") { echo "value='{$query}'"; } ?> />
			<button type="submit">Search</button>
		</form>

		<?php
			foreach ($comics as $strip) {
				$bolded = htmlspecialchars($strip["ocr"]);
				$bolded = preg_replace("/\n/", "<br>", preg_replace("/\n\n/", "\n", $bolded));
				if ($query !== "") {
					$words = explode(" ", $query);
					foreach ($words as $word) {
						$bolded = preg_replace("/" . $word . "/i", "<span class='found'>\$0</span>", $bolded);
					}
				}

				echo "
					<div class='comic'>
						<div class='title'><span>" . date('F j, Y', strtotime($strip['date'])) . "</span><a href=\"https://www.gocomics.com/pearlsbeforeswine/".date('Y/m/d', strtotime($strip['date']))."\" target=\"_blank\">🔗&#xFE0E;</a></div>
						<div>
							<div class='image'>
								<img src='{$strip['small_url']}' />
							</div>
							<p class='ocr'>
								{$bolded}
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