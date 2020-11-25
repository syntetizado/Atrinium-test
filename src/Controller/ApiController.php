<?php

namespace App\Controller;

// Basic controller classes
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// Import HTTP request and response handlers
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// Import the API service
use App\Services\ApiService;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ApiController extends AbstractController
{
    protected DateTime $date;
    /**
     * @Route("/api", name="api")
     */
    public function index(ApiService $apiService, Request $request): Response
    {
        $coins = $apiService->getCoinNames();

        // Create the form
        $form = $this->createFormBuilder()
                        ->add('coinFrom', ChoiceType::class,[
                            'label' => 'From: ',
                            'choices' => array_flip($coins['symbols'])
                        ])
                        ->add('coinTo', ChoiceType::class,[
                            'label' => 'To: ',
                            'choices' => array_flip($coins['symbols'])
                        ])
                        ->add('amount', NumberType::class,[
                            'label' => 'Amount: '
                        ])
                        ->add('date', DateType::class,[
                            'widget' => 'single_text',
                            'html5' => false,
                        ])
                        ->getForm();

        // Check form request
        $form->handleRequest($request);



        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            //get all form data
            $coinFrom = $form->get('coinFrom')->getData();
            $coinTo = $form->get('coinTo')->getData();
            $amount = $form->get('amount')->getData();
            $date = $form->get('date')->getData();

            //get data exchange data from that date
            $data = $apiService->getDateExchange($date->format('Y-m-d'));

            //get the specific 2 rates we want
            $rate1 = $data['rates'][$coinFrom];
            $rate2 = $data['rates'][$coinTo];

            $conversionRatio = $rate2/$rate1;

            $conversion = $amount*$conversionRatio;

            $conversionData = [];
            $conversionData['rate1'] = $rate1;
            $conversionData['rate2'] = $rate2;
            $conversionData['coin1'] = $coinFrom;
            $conversionData['coin2'] = $coinTo;
            $conversionData['conversionRatio'] = $conversionRatio;
            $conversionData['input'] = $amount;
            $conversionData['conversion'] = $conversion;
            $conversionData['from'] = $coins['from'];

            return $this->render('api/exchange-results.html.twig', [
                'controller_name' => 'ApiController',
                'data' => $data,
                'conversionData' => $conversionData,
            ]);
        }
        else
        {
            return $this->render('api/exchange-form.html.twig', [
                'form' => $form->createView()
            ]);
        }
    }
}
