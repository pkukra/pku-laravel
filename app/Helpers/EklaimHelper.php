<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

if (! function_exists('mc_encrypt')) {
    function mc_encrypt($data, $key)
    {
        /// make binary representasion of $key
        $key = hex2bin($key);
        /// check key length, must be 256 bit or 32 bytes
        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }
        /// create initialization vector
        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        $iv = openssl_random_pseudo_bytes($iv_size);
        /// encrypt
        $encrypted = openssl_encrypt($data, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
        /// create signature, against padding oracle attacks
        $signature = mb_substr(hash_hmac("sha256", $encrypted, $key, true), 0, 10, "8bit");
        /// combine all, encode, and format
        $encoded = chunk_split(base64_encode($signature . $iv . $encrypted));
        return $encoded;
    }
}

if (! function_exists('mc_compare')) {
    /// Compare Function
    function mc_compare($a, $b)
    {
        /// compare individually to prevent timing attacks
        /// compare length
        if (strlen($a) !== strlen($b)) return false;
        /// compare individual
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $result == 0;
    }
}

if (! function_exists('mc_decrypt')) {
    /// Decryption Function
    function mc_decrypt($str, $strkey)
    {
        /// make binary representation of $key
        $key = hex2bin($strkey);
        /// check key length, must be 256 bit or 32 bytes
        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }
        /// calculate iv size
        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        /// breakdown parts
        $decoded = base64_decode($str);
        $signature = mb_substr($decoded, 0, 10, "8bit");
        $iv = mb_substr($decoded, 10, $iv_size, "8bit");
        $encrypted = mb_substr($decoded, $iv_size + 10, NULL, "8bit");
        /// check signature, against padding oracle attack
        $calc_signature = mb_substr(hash_hmac("sha256", $encrypted, $key, true), 0, 10, "8bit");
        if (!mc_compare($signature, $calc_signature)) {
            return "SIGNATURE_NOT_MATCH"; /// signature doesn't match
        }
        $decrypted = openssl_decrypt($encrypted, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
}

if (! function_exists('sendRequest')) {
    function sendRequest($key, $data)
    {
        $encryptedData = mc_encrypt($data, $key); // enkripsi data sebelum dikirim

        $url = env("EKLAIM_WS_URL");
        $client = new Client();

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ],
                'body' => $encryptedData,
                'timeout' => 5,  // Maksimal waktu tunggu response 5 detik
                'connect_timeout' => 5, // Maksimal waktu koneksi 5 detik
            ]);

            $responseBody = $response->getBody()->getContents();

            // Membersihkan response dari karakter tak diinginkan
            $first = strpos($responseBody, "\n") + 1;
            $last = strrpos($responseBody, "\n") - 1;
            $responseBody = substr($responseBody, $first, strlen($responseBody) - $first - $last);

            // Dekripsi response
            $decryptedResponse = mc_decrypt($responseBody, $key);

            return (object)[
                "status" => "ok",
                "error" => null,
                "response" => json_decode($decryptedResponse)
            ];
        } catch (ConnectException $ce) {
            Log::error('Error e-klaim helper sendRequest ConnectException (server down?): ' . $ce->getMessage());
            return (object)[
                "status" => "nok",
                "error" => "Tidak dapat terhubung ke server e-Klaim."
            ];
        } catch (RequestException $e) {
            $message = $e->getMessage();
            if (str_contains(strtolower($message), 'timed out')) {
                $error = "Request ke server e-Klaim timed out.";
            } else {
                $error = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $message;
            }

            Log::error('Error e-klaim helper sendRequest RequestException: ' . $message);
            return (object)[
                "status" => "nok",
                "error" => $error
            ];
        } catch (\Throwable $th) {
            Log::error('Error e-klaim helper sendRequest Throwable: ' . $th->getMessage());
            return (object)[
                "status" => "nok",
                "error" => $th->getMessage()
            ];
        }
    }
}
