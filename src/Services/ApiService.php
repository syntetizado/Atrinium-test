<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Exchange;
use App\Entity\ApiCall;

class ApiService
{
    protected $entityManager;
    private $access_key = 'bbfa33aae96051572e483889b36ed45b';

    /**
     * @param HttpUtils $httpUtils
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDateExchange($inputDate) //string in YYYY-MM-DD format
    {
        $data = [];
        $inputDate = new \DateTime($inputDate);
        $exchangeRepo = $this->entityManager->getRepository(Exchange::class);

        $exchange = $exchangeRepo->findOneByDate($inputDate);
        // check if date of that day is stored on database
        if ($exchange) {
            // it exists on database, so we proceed to take data from database
            $json = $exchange->getExchangeData();
            $data = json_decode($json, true);
            $data['from'] = 'database';

        } else {
            // set API Endpoint
            $endpoint = $inputDate->format('Y-m-d'); //date in YYYY-MM-DD
            $access_key = 'bbfa33aae96051572e483889b36ed45b';

            // initialize CURL: ( http://data.fixer.io/api/2013-03-16?access_key=... )
            $ch = curl_init( 'http://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // get the JSON data:
            $json = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($json, true);
            $data['from'] = 'api';

            if ($data['success']) {
                $exchange = new Exchange();

                $exchange->setDate($inputDate);
                $exchange->setExchangeData($json);

                $entityManager = $this->entityManager;
                $entityManager->persist($exchange);
                $entityManager->flush();
            }
        }

        return $data;
    }

    public function getCoinNames()
    {
        $data = [];
        $apiCallRepo = $this->entityManager->getRepository(ApiCall::class);

        $apiCall = $apiCallRepo->findOneByName('fixed-io > coin-names');
        //Update to database only if we want
        if ($apiCall) {
            // we will take data from DB
            $json = $apiCall->getData();
            $data = json_decode($json, true);
            $data['from'] = 'database';

        } else {
            // set API Endpoint
            $endpoint = 'symbols'; //date in YYYY-MM-DD

            // initialize CURL: ( http://data.fixer.io/api/2013-03-16?access_key=... )
            $ch = curl_init( 'http://data.fixer.io/api/'.$endpoint.'?access_key='.$this->access_key );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // get the JSON data:
            $json = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($json, true);
            $data['from'] = 'api';

            if ($data['success']) {
                $apiCall = new ApiCall();

                $apiCall->setName('fixed-io > coin-names');
                $apiCall->setData($json);

                $entityManager = $this->entityManager;
                $entityManager->persist($apiCall);
                $entityManager->flush();
            }
        }

        return $data;
    }
}
