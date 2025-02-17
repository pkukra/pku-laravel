<?php

namespace App\Repositories\RM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class EklaimRepository
{
    /**
     * Get the klaim data dari nomer sep
     * 
     * @param string $no_sep
     * @return \Illuminate\Support\Collection
     */
    public function getKlaimData($no_sep)
    {
        $client = new Client();

        $url = env("EKLAIM_WS_URL", "");
        $request = json_encode((object)[
            'metadata' => (object)[
                'method' => 'get_claim_data'
            ],
            'data' => (object)[
                'nomor_sep' => $no_sep,
            ]
        ]);

        $key = "3286e120fea9b340164f0c76c50bbf0f529746666ce38e2d372dd2b4c5f0a946";
        $data = mc_encrypt($request, $key);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $response = $client->post($url, [
            'headers' => $headers,
            'body'    => $data
        ]);

        $response = $response->getBody()->getContents();

        $first = strpos($response, "\n") + 1;
        $last = strrpos($response, "\n") - 1;
        $response = substr($response, $first, strlen($response) - $first - $last);
        $response = mc_decrypt($response, $key);

        return $response;
    }
}
