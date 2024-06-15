<?php
    include_once("secret.php");
    include_once("reddit.php");

    error_reporting(E_ALL);
    ini_set('display_errors', 'On');

    // Add in an exception for CLI
    if (isset($argv[1])) {
        if ($argv[1] !== DOWNLOAD_CODE) {
            echo 'Incorrect download code.';
            exit; 
        }
        $date = date('Y-m-d');
    } else {
        // Confirm that you have the special code
        $dl_code = $_GET["dl"] ?? "";
        if ($dl_code !== DOWNLOAD_CODE) {
            echo 'Incorrect download code.';
            exit;
        }

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
    }

    function getOCR($image_url) {
        $data = [
            "model" => "gpt-4o",
            "messages" => [
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Please transcribe the text from this image. Don't include any details about panels and characters, and just return the full spoken text. Please don't add in unwritten quotation marks, and please add newlines between speech bubbles. Please also try to match the capitalization of the image (i.e. if the text is all uppercase, keep it in uppercase)."
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => $image_url,
                                "detail" => "low",
                            ]
                        ]
                    ]
                ]
            ],
            "max_tokens" => 300
        ];

        $ch = curl_init();
    
        $jsonData = json_encode($data);
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPEN_AI_TOKEN
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        // Check for cURL errors
        if ($response === false) {
            $error = curl_error($ch);
            return [
                'status' => false,
                'message' => $error,
            ];
        } else {
            // Close the cURL session
            curl_close($ch);

            $real_response = json_decode($response, true);
            if ($real_response === false) {
                return [
                    'status' => false,
                    'message' => 'JSON decoding failed.',
                ];
            }
            // Process the response
            if (!array_key_exists('choices', $real_response)) {
                return [
                    'status' => false,
                    'message' => 'No choices in response.',
                ];
            }
            $choices = $real_response['choices'];
            if (!is_array($choices) || count($choices) === 0) {
                return [
                    'status' => false,
                    'message' => 'Empty choices in response.',
                ];
            }
            return [
                'status' => true,
                'message' => $choices[0]['message']['content'],
            ];
        }
    }

    // Get the image
    $website = 'https://www.gocomics.com/pearlsbeforeswine/'.date('Y/m/d', strtotime($date));
    preg_match("/data-image=\"(https:\/\/assets.amuniversal.com\/.*?)\"/", file_get_contents($website), $matches);
    if (!array_key_exists(1, $matches)) {
        echo 'Failed to find comic.';
        exit;
    }
    $comic_url = $matches[1];

    // Get the OCR through ChatGPT
    $ocr = getOCR($comic_url);

    // Insert it into the SQL database
    $PDO = createConnection();

    // If it failed, skip the OCR fields
    try {
        if (!$ocr['status']) {
            $stmt = $PDO->prepare("INSERT INTO strips(`url`, `small_url`, `date`) VALUES (:url, :small, :date)");
            $stmt->bindValue(":url", $comic_url, PDO::PARAM_STR);
            $stmt->bindValue(":small", $comic_url, PDO::PARAM_STR);
            $stmt->bindValue(":date", date('Y-m-d', strtotime($date)), PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $stmt = $PDO->prepare("INSERT INTO strips(`url`, `small_url`, `date`, `ocr`, `ocr_updated`) VALUES (:url, :small, :date, :ocr, :ocr_updated)");
            $stmt->bindValue(":url", $comic_url, PDO::PARAM_STR);
            $stmt->bindValue(":small", $comic_url, PDO::PARAM_STR);
            $stmt->bindValue(":date", date('Y-m-d', strtotime($date)), PDO::PARAM_STR);
            $stmt->bindValue(":ocr", $ocr['message'], PDO::PARAM_STR);
            $stmt->bindValue(":ocr_updated", 1, PDO::PARAM_INT);
            $stmt->execute();
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo 'Already inserted for this date. Aborting.';
            exit;
        } else {
            throw $e;
        }
    }

    // Try and post to Reddit
    $access_token = getAccessToken();

    // Create a Reddit post
    $title = "Pearls Before Swine | " . date('l, F j, Y', strtotime($date));
    $uploaded_url = uploadMedia($access_token, $comic_url);
    $response = createRedditPost($access_token, $title, $uploaded_url);

    // Hurrah!
    echo 'Updated ' . date('Y/m/d', strtotime($date)) . '.' . ($ocr['status'] ? '' : ' (OCR failed - ' . $ocr['message'] . ').');
?>