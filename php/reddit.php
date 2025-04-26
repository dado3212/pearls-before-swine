<?php

include_once("secret.php");

error_reporting(E_ALL);
ini_set('display_errors', 'On');

// Function to get the access token
function getAccessToken() {
    $url = 'https://www.reddit.com/api/v1/access_token';
    $data = array(
        'grant_type' => 'password',
        'username' => 'Pearls_Bot',
        'password' => REDDIT_PASSWORD,
    );

    $headers = [
        'Authorization: Basic ' . base64_encode(REDDIT_CLIENT_ID . ':' . REDDIT_CLIENT_SECRET),
        'User-Agent: script:pearls_comics_bot:v1.0 (by /u/Pearls_Bot)',
    ];

    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    $body = json_decode($response, true);
    return $body['access_token'];
}

function uploadMedia($access_token, $image_url) {
    // Undocumented API adapted from PRAW
    $url = 'https://oauth.reddit.com/api/media/asset.json';

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'User-Agent: script:pearls_comics_bot:v1.0 (by /u/Pearls_Bot)',
    ];

    // Download the image data from the URL
    $image_data = file_get_contents($image_url);
    $image_temp_name = basename($image_url);

    // Create a temporary file to store the downloaded image
    $tmp_filename = sys_get_temp_dir() . '/' . $image_temp_name;
    file_put_contents($tmp_filename, $image_data);
    $content_type = mime_content_type($tmp_filename);

    // If it's a GIF, convert it to JPG
    if ($content_type === 'image/gif') {
        $old_filename = $tmp_filename;
        $gif_img = imagecreatefromgif($tmp_filename);
        $image_temp_name = $image_temp_name . '1';
        $tmp_filename = sys_get_temp_dir() . '/' . $image_temp_name;
        imagejpeg($gif_img, $tmp_filename, 100);
        unlink($old_filename);
    }

    // Prepare the image for cURL
    $data = [
        'filepath' => $tmp_filename,
        'mimetype' => 'image/jpeg',
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    $body = json_decode($response, true);

    $upload_url = 'https:' . $body['args']['action'];
    $upload_data = [];
    foreach ($body['args']['fields'] as $field) {
        $upload_data[$field['name']] = $field['value'];
    }
    $image_file = curl_file_create($tmp_filename, 'image/jpeg', $image_temp_name);
    $upload_data['file'] = $image_file;

    // Actually upload it
    $options = [
        CURLOPT_URL => $upload_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $upload_data,
        CURLOPT_RETURNTRANSFER => true,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $aws_response = curl_exec($ch);
    curl_close($ch);
    unlink($tmp_filename);

    // $aws_response can fail but like, ignore that for now :D

    return $upload_url . '/' . $upload_data['key'];
}

// Function to create a Reddit post
function createRedditPost($access_token, $title, $image_url) {
    $url = 'https://oauth.reddit.com/api/submit';
    $data = [
        'sr' => 'pearlsbeforeswine', // subreddit
        'title' => $title,
        'kind' => 'image',
        'url' => $image_url,
    ];

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'User-Agent: script:pearls_comics_bot:v1.0 (by /u/Pearls_Bot)',
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    $body = json_decode($response, true);
    return $body;
}
?>