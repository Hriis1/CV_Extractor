<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $filePath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];

        $apiKey = 'tRZEvaZOSdFGKqAjJkHGxPTBiHfHquHsYFaPLcYVPvweZPQXho';
        $url = 'https://converter.portal.ayfie.com/api/converter/1/FileConverter/Convert';

        $curl = curl_init();

        $file = new CURLFile($filePath, mime_content_type($filePath), $fileName);
        $postFields = ['file' => $file];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                "X-API-KEY: $apiKey",
                "Content-Type: multipart/form-data"
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $responseArray = json_decode($response, true);
            print_r($responseArray['text']);
        }
    } else {
        echo 'No file uploaded.';
    }
} else {
    echo 'Invalid request method.';
}
