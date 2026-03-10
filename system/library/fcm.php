<?php

class Fcm {
    private $registry;
    private $credentials = [
        "type"                        => "service_account",
        "project_id"                  => "detta-app",
        "private_key_id"              => "b11f4f679cd48594d6f38ecdd68ab9056273f3b4",
        "private_key"                 => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDClt4GBxE5VCo2\n65b5vWxyLR/5CzP/Jd+YRr1yRFCi3yN98J4uHNj2rFaowo9+Z694ltZKL88VUxiW\n3tFkdjMsVHNpv+PjJKJxI0POFE5e5Vxl9rkxyrmFbIMkN4QmzpVjlxJZIL/u4Hfn\nt7T6CVf9fK5NlH10pGX7R+ftKDMxwah+tNhkETtgXR8wYkSWM0wnudjVkgP8T/kH\nqejOJSNecYTHyriuweZJ52anGfpzMMh3WVOXK6Nt3v9Mo0AVPGgJI+jLTcAdnLED\nTHmBotYQIHcHa3Gs0mT2EFB/d/LCk8qtmVxMy9G+fig3fwRFkGe4VhJy6uZZLk/g\nvN74fnYRAgMBAAECggEADhRBlWjdGF6V/PB7NWMJ13g0w2pjfu2FlqX5Pq0UsuqE\nNEnv/mDvjc5J9hHsG5A/xCQQ9lmSzFv9ZnhR6PvoZFrqTHEQeWnRwW/bKXj5c3V5\nUJaGYpGxbkCIrvRZWnMOBtOlnzWqAKWhgO5SaNjuORTUiiAMgVKeK/OeI0ZwvDb1\n8rRgh8lpV4u4nc5eK5jLGgmD1p8FLDiweg+4qbXstyVi8cCWLztHIELAMlO2cWfu\nzN3p9zLDHRf2UlB+aGlY50H+BxfbuPeU6PwBjKAbhRtiYt3C2xIodYKbGOW7VUim\nY9ZW+6e9UW4MxIkrNR1t0c/OjYsTpy3smV8G6MdbFQKBgQDidnyxsEx3M3GswGP8\niuVZIy3uo5NFqEpdrp/4Yz6aJ6Zd9SMIq87dxMwosf+0mFCa4zBGEKFuKyRAtD9S\n5sqNQ2w0Css1isZT3loYwKnHu3xgEHRTGRkz2py/Z0AK5vsqrrEGy7r2cEvkWD8r\n39Hjz7i36rjMeUm+Xd9VqEHGYwKBgQDb+CKN2oCw/Y7e3CdQQ1XLN1kpfoOxyjsE\nZnMUTpnJC6mZQk5JdH5QS3m9SgfSSDEYRm7jzvPQLnKP06hP0o9IWJIHVo8x+l6r\nHZOZrU6x0O0m5jMSZUhAukfK/xoELdTfHRy5CV4zgzmbjJuryb/eVhRE9gx7g54u\nGnvTHVgx+wKBgACC6/0qvMF4KEWPmao0VhhBcBUd4XNC0ggsIMha0QVgGYwUxaN7\nX9g4XY2p+T3bKjNvV+iQmQy6pDZRMeNqCgMPp+rmK1dPnOsLkYCEzt1YmwtMfjbB\n08C+OaRlA0wDAYYzJssxIpbz4ff+CwZ6VusAyRYBPbGYhIYdiCeVXbCpAoGBANUG\nXRBbhz3gkLgrJLeKPk/rbiHNL+TCIJ2GSfRkmnIlJT4TBJYGhz1jmqZCR4jR+Rm1\nPDbKeTwnfzLim6GSHMjHXcRVg5+3BG9a2VJ+kDOMTd7aGKO8ClkFDfn9S0i4yeq4\n2tQnyl7Aus11Qlz/qRy86CxQzI3hTRMA+uHdDUh5AoGAPB61JKEZ1LaXkn7TsW1O\nIXheShZpC0YGTWwZPzRM5UtKhccvw3lNtEE0VxJVzWYkOPa9ZFO0YjXMkO8rr89i\nqTbId/Y+4+9i/bexEhRRpnVIqVq1Mt6wzW8SvBQMBVVsqlNZEItu9Ny06nqSKJaZ\n4UgZhJhlye0Qd/yGPyPJDUE=\n-----END PRIVATE KEY-----\n",
        "client_email"               => "firebase-adminsdk-fbsvc@detta-app.iam.gserviceaccount.com",
        "client_id"                   => "116316923470423809234",
        "auth_uri"                    => "https://accounts.google.com/o/oauth2/auth",
        "token_uri"                   => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url"         => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40detta-app.iam.gserviceaccount.com",
        "universe_domain"             => "googleapis.com"
    ];

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function send($token, $data = []) {
        if (!$token) return ['error' => 'No token provided'];

        $projectId = $this->credentials['project_id'];
        $accessToken = $this->getAccessToken();

        if (!$accessToken) return ['error' => 'Could not get access token'];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $message = [
            "message" => [
                "token" => $token,
                "data"  => $data
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'error'    => "FCM request failed with code $httpCode",
                'response' => $response
            ];
        }

        return ['response' => $response];
    }

    private function getAccessToken() {
        $now = time();
        $payload = [
            'iss'   => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ];

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        $privateKey = str_replace('\n', "\n", $this->credentials['private_key']);
        
        if (!openssl_sign($signatureInput, $signature, $privateKey, 'SHA256')) {
            return false;
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);
        $jwt = $signatureInput . "." . $base64UrlSignature;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return isset($data['access_token']) ? $data['access_token'] : false;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
