<?php
namespace App\Service;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InfuraApiService
{
    public function __construct(
        private SerializerInterface $serializer,
        private HttpClientInterface $httpClient,
        )
        {
        }

    public function getEthBalance(string $address, $rpcUrl)
    {
        // mise en forme de la nouvelle requette API
        $response = $this->httpClient->request('POST', $rpcUrl, [
            'headers' => ['Content-Type' => 'application/json',],
            'body' => json_encode([
                'jsonrpc' => '2.0',
                'method' => 'eth_getBalance',
                'params' => [
                    $address,
                    'latest'
                ],
                'id' => 1  
            ])
        ]);
         $jsonreponse = $response->getContent();
         $data = json_decode($jsonreponse, true);
         $balance  = hexdec($data['result']) / 1e18;
        return $balance;
    }
}