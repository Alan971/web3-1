<?php 
namespace App\Service;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoinMarketCapApiService  
{
    public function __construct(
        private SerializerInterface $serializer,
        private HttpClientInterface $httpClient,
        )
        {
        }

    public function getTokenPriceBySymbol(string $cmcApiKey, $symbol): array
    {
        $url = "https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest?symbol={$symbol}&CMC_PRO_API_KEY={$cmcApiKey}";
        $response = $this->httpClient->request('GET', $url);
        $jsonResponse = $response->getContent();
        $data = json_decode($jsonResponse, true);
    
        if ($data['status']['error_code'] !== 0) {
            throw new \Exception("Error fetching token balances: " . $data['status']['error_code'] . ". Caused by :" . $data['status']['error_message']);
        }
        dump($data['data']);
        return $data['data'];

    }

}