<?php 
namespace App\Service;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EtherscanApiService
{
    public function __construct(
        private SerializerInterface $serializer,
        private HttpClientInterface $httpClient,
        )
        {
        }

    public function askEtherscanAnything(string $address, string $etherscanApiKey, $module, $action): array
    {
        $url = "https://api.etherscan.io/api?module=" . $module . "&action=" . $action . "&address={$address}&tag=latest&apikey={$etherscanApiKey}";
        
        $response = $this->httpClient->request('GET', $url);
        $jsonResponse = $response->getContent();
        $data = json_decode($jsonResponse, true);
    
        if ($data['status'] !== '1') {
            throw new \Exception("Error fetching token balances: " . $data['message'] . ". Caused by :" . $data['result']);
        }
        $tokenBalances = [];
        return $data;

    }

    public function getEthBalance (string $address, string $etherscanApiKey, $module, $action)
    {
        $data = $this->askEtherscanAnything($address, $etherscanApiKey, $module, $action);
        // mise en forme du résultat (dépréciation de l'information contenue dans $data)
        dump($data);
        return $data['result'] / 1e18;
        
    }

    public function getEthBalances (string $address, string $etherscanApiKey, $module, $action)
    {
        $data = $this->askEtherscanAnything($address, $etherscanApiKey, $module, $action);
        // mise en forme du résultat (dépréciation de l'information contenue dans $data)
        foreach ($data['result'] as $tokentx) {
            $tokenBalances[] = [
                'tokenSymbol' => $tokentx['tokenSymbol'],
                'value' => $tokentx['value'],
                'tokenDecimal' => $tokentx['tokenDecimal'],
                'tokenFrom' => $tokentx['from'],
                'tokenTo' => $tokentx['to']
            ];
        }
        //on additionne ou soustrait chaque transaction de symbole identique
        $count = 0;
        foreach ($tokenBalances as $tokentx) {
            if( $tokentx['tokenFrom'] === $address) {
                $tokentx['value'] = - $tokentx['value'];
            }
            for($i = 0; $i < $count; $i++) {
                if($tokenBalances[$i]['tokenSymbol'] === $tokentx['tokenSymbol']) {
                    $tokenBalances[$i]['value'] += $tokentx['value'];
                    $tokenBalances[$count]['tokenSymbol']  = "";
                    break;
                }
            }
            $count++;
        }
        $count = 0;
        foreach ($tokenBalances as $tokentx) {
            if($tokentx['tokenSymbol'] !== "") {
                $allTokenBalances[] = $tokentx;
            }
            $count++;
        }

        return $allTokenBalances;
    }
}