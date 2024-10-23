<?php 
namespace App\Service;

use Web3\Web3;

class EthereumService
{
    private $web3;

    public function __construct(string $rpcUrl = "")
    {
        //$this->web3 = new Web3($this->getParameter('rpcUrl'));
    }

    public function getAccountBalance(string $address) 
    {
            $this->web3->eth->accounts(function ($err, $accounts) use ($address) {
                echo "adresses: " . $address. PHP_EOL;
                echo "accounts:" . $accounts . PHP_EOL;
                if($address === null) {
                    throw new \Exception("Error on null address");
                }
                if ($err !== null) {
                    throw new \Exception("Error fetching balance: " . $err->getMessage());
                }
                foreach ($accounts as $account) {
                    echo 'Account: ' . $account . PHP_EOL;
                    $this->web3->eth->getBalance($account, function ($err, $balance) {
                        if ($err !== null) {
                            echo 'Error: ' . $err->getMessage();
                            return;
                        }
                        echo 'Balance: ' . $balance . PHP_EOL;
                    });
                }
            });
            $this->web3->eth->getBalance($address, function ($err, $balance) {
                if ($err) {
                    throw new \Exception("Error fetching balance: " . $err->getMessage());
                }
                if ($balance === null) {
                    throw new \Exception("Balance is null");
                }
                dump( hexdec($balance->toString())/1e18);
                // Convertir le solde en Ether
                return hexdec($balance->toString()) / 1e18; // Convertir en eth
            });
    }
    
}
