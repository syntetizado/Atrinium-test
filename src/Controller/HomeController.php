<?php

namespace App\Controller;

// Basic controller classes
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// Import HTTP request and response handlers
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @Route("/crud", name="crud")
     */
    public function crudIndex(): Response
    {
        return $this->render('crud/index.html.twig', [
            'controller_name' => 'CrudController',
        ]);
    }

    /**
     * @Route("/go-back", name="go-back")
     */
    public function goBack(Request $request)
    {
        $requestURI=$request->get('currentRoute');

        $after_bar = strrchr($requestURI, '/');

        //deletes last bar in uri
        $url = str_replace($after_bar,'',$requestURI);

        if ($url== '')
        {
            $url = "/";
        }

        //goes back 1 bar less
        return $this->redirect($url);
    }
}
