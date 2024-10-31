<?php

namespace App\Controller;

use App\Service\EtherscanApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\InfuraApiService;
use App\Service\CoinMarketCapApiService;

class ApiController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private HttpClientInterface $httpClient,
        )
        {
        }
    #[Route('infura/{address}', name: 'app_api_infura')]
    public function infura(string $address): Response
    {
        $balance = new InfuraApiService( $this->serializer, $this->httpClient);
        $balance = $balance->getEthBalance($address, $this->getParameter('rpcUrl'));
        return $this->render('api/infura.html.twig', [
            'address' => $address,
            'balance' => $balance,
        ]);
    }

    /**
     * call the ethscan api
     * 
     * 
     */
    #[Route('ethscan/{address}', name: 'app_api_ethscan')]
    public function ethscan(string $address): Response
    {
        // récupération des balances de l'adresse
        $balance = new EtherscanApiService( $this->serializer, $this->httpClient);
        $balances = $balance->getEthBalances(
            $address, 
            $this->getParameter('etherscanApiKey'), 
            "account", //module
            "tokentx",  //action
        );
        /* TODO
        * Cette partie filtre est à sortir du controleur, entrée balances sortie balances filtrées
        */
        // filtre les petites valeurs
        $balances = array_filter($balances, function($balance) {
            return $balance['value'] / 10 ** $balance['tokenDecimal'] > 0.001;
        });
        //filtre les scams
        // Lire le fichier JSON blacklist.json
        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $jsonFilePath = $publicDir . '/blacklistSymblos.json';
        $jsonContent = file_get_contents($jsonFilePath);
        // Décoder le contenu JSON en tableau
        $symbolsList = json_decode($jsonContent, true);
        // Vérifier si la décodage a réussi et que c'est un tableau
        if (!is_array($symbolsList)) {
            throw new \Exception('Le fichier JSON ne contient pas un tableau valide.');
        }
        $balances = array_filter($balances, function($balance) use ($symbolsList) {
            return strlen($balance['tokenSymbol']) < 6;
        });
        $balances = array_filter($balances, function($balance) use ($symbolsList) {
            return !in_array($balance['tokenSymbol'], $symbolsList);
        });

        $ethBalance = $balance->getEthBalance(
            $address, 
            $this->getParameter('etherscanApiKey'),
            "account", //module
            "balance", //action
        );
        // concaténation des symboles
        $symbolArray = array_column($balances, 'tokenSymbol');
        $symbol = implode(',', $symbolArray);

        // récupération des prix des tokens
        $tokenPrice = new CoinMarketCapApiService( $this->serializer, $this->httpClient);
        $tokenPrice = $tokenPrice->getTokenPriceBySymbol(
            $this->getParameter('CoinMarketCapApiKey'), $symbol
        );
        // ajout des valeurs dans le tableau des balances
        foreach ($balances as &$balance) {
            foreach ($symbolArray as $symbol) {
                if($balance['tokenSymbol'] === $symbol && array_key_exists($symbol, $tokenPrice)) {
                    $balance['priceUnit'] = round($tokenPrice[$symbol]['quote']['USD']['price'], 2);
                    $balance['price'] = round((float) $balance['value'] / 10 ** $balance['tokenDecimal'] * (float) $balance['priceUnit'], 2);
                    $balance['img'] = "https://s2.coinmarketcap.com/static/img/coins/64x64/" .$tokenPrice[$symbol]['id'] . ".png";
                }
            }
        }
        unset($balance);
        return $this->render('api/ethscan.html.twig', [
            'address' => $address,
            'balances' => $balances,
            'ethBalance' => $ethBalance,
        ]);
    }

    /*
    * valeurs que peut prendre $module :
    * - account
    * - token
    * - contract
    * - Transaction
    * - block
    * - event

    * valeurs que peut prendre $action :
    * - balance
    * - balancemulti
    * - txlist      &startblock=0
                    &endblock=99999999
                    &page=1
                    &offset=10
                    &sort=asc
    * - txlistinternal
    * - tokentx
    */

}
