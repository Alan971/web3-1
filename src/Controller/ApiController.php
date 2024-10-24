<?php

namespace App\Controller;

use App\Service\EtherscanApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\InfuraApiService;

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
        $balance = new EtherscanApiService( $this->serializer, $this->httpClient);
        $balances = $balance->getEthBalances(
            $address, 
            $this->getParameter('etherscanApiKey'), 
            "account", //module
            "tokentx",  //action
        );
        $ethBalance = $balance->getEthBalance(
            $address, 
            $this->getParameter('etherscanApiKey'),
            "account", //module
            "balance", //action
        );

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
