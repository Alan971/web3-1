<?php

namespace App\Controller;

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
    #[Route('api/{address}', name: 'app_api')]
    public function index(string $address): Response
    {
        $balance = new InfuraApiService( $this->serializer, $this->httpClient);
        $balance = $balance->getEthBalance($address, $this->getParameter('rpcUrl'));
        return $this->render('api/index.html.twig', [
            'address' => $address,
            'balance' => $balance,
        ]);
    }
}
