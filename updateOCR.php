<?php

include("php/secret.php");

error_reporting(E_ALL);
ini_set('display_errors', 'On');

if (isset($_GET["q"]) && strlen($_GET["q"]) > 0) {
    $query = $_GET["q"];
} else {
    $query = "";
}
if ($query !== CODE) {
    echo "no no no!";
    exit;
}

function getOCRParams($image) {
    return [
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
                            "url" => $image,
                            "detail" => "low",
                        ]
                    ]
                ]
            ]
        ],
        "max_tokens" => 300
    ];
}

function processResponse($response) {
    // Check for cURL errors
    if ($response === false) {
        $error = curl_error($ch);
        return [
            'status' => false,
            'message' => $error,
        ];
    } else {
        // echo var_export(print_r($response, true));
        $real_response = json_decode($response, true);
        // echo '<br />';
        // echo var_export(print_r($real_response, true));
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

$PDO = createConnection();

$stmt = $PDO->prepare("SELECT * FROM strips WHERE ocr_updated = FALSE ORDER BY date ASC LIMIT 25");
$stmt->execute();

$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);

$multiHandle = curl_multi_init();
$curlHandles = [];

foreach ($comics as $comic) {
    $ch = curl_init();
    
    $jsonData = json_encode(getOCRParams($comic['url']));
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPEN_AI_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Add the individual cURL handle to the multi handle
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = [$ch, $comic];
}

$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running);

$responses = [];
foreach ($curlHandles as $ch_info) {
    list($ch, $comic) = $ch_info;
    $response = curl_multi_getcontent($ch);
    $responses[] = [processResponse($response), $comic, $response];
    
    // Close individual cURL handles
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

// Close the multi handle
curl_multi_close($multiHandle);

foreach ($responses as $response) {
    list($ocr_info, $comic, $raw_response) = $response;

    if (!$ocr_info['status']) {
        echo 'Error on ' . $comic['date'] . ' - '. $ocr_info['message'];
        echo var_export(print_r($raw_response, true));
        echo '<br /><br />';
    } else {
        try {
            $stmt = $PDO->prepare("UPDATE strips SET ocr_updated = TRUE, `ocr`=:text WHERE id=:id");
            $stmt->bindValue(":text", $ocr_info['message'], PDO::PARAM_STR);
            $stmt->bindValue(":id", $comic['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            echo var_export(print_r($ocr_info, true));
            echo '<br /><br />';
        } catch (Exception $e) {
            echo $e->getMessage();
            echo '<br /><br />';
        }
    }
}
