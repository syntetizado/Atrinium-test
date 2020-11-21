<?php

namespace App\Controller;

// Basic controller classes
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// Import HTTP request and response handlers
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// Import necesary classes for login and security
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;

// Import needed Entities
use App\Entity\Sector;
use App\Entity\User;
use App\Entity\UserSector;

// Import form type classes
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class SectorController extends AbstractController
{
    /**
     * @Route("/crud/sectors/new", name="create-sector")
     */
    public function createSector(Security $security, Request $request): Response
    {
        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user) {
            $role = $user->getIdRole()->getCodename();
        } else {
            return $this->redirectToRoute('login');
        }

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN') {
            return $this->redirectToRoute('login');
        }

        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);

        // Create the form
        $form = $this->createFormBuilder()
                        ->add('name', TextType::class,['label' => 'Name:'])
                        ->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid()){

            $name = $form->get('name')->getData();

            if ($sector_repo->findOneByName($name)) {
                return $this->render('crud/sector/add-sector.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That sector name already exists.'
                ]);
            }

            $sector = new Sector();
            $sector->setName($name);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sector);
            $entityManager->flush();

            return $this->RedirectToRoute('sectors', ['success' => "The sector '".$form->get('name')->getData()."' has been created."]);
        } else {
            return $this->render('crud/sector/add-sector.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/crud/sectors/edit/{id}", name="edit-sector")
     */
    public function editSector(Security $security, Request $request, $id): Response
    {
        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user) {
            $role = $user->getIdRole()->getCodename();
        } else {
            return $this->redirectToRoute('login');
        }

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN') {
            return $this->redirectToRoute('login');
        }

        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
        $sector = $sector_repo->findOneById($id);

        // Create the form
        $form = $this->createFormBuilder()
                        ->add('name', TextType::class,[
                            'label' => 'Name:',
                            'data' => $sector->getName(),
                        ])
                        ->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid()){

            $name = $form->get('name')->getData();

            if ($sector->getName() == $name) {
                return $this->RedirectToRoute('sectors', [
                    'modal' => "Nothing changed",
                    'message' => "The sector '".$form->get('name')->getData()."' has the same name.",
                    'button' => "ok",
                ]);
            }

            if ($sector_repo->findOneByName($name)) {
                return $this->render('crud/sector/edit-sector.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That sector name already exists.'
                ]);
            }
            $sector->setName($name);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sector);
            $entityManager->flush();

            return $this->RedirectToRoute('sectors', [
                'modal' => "Sector edited",
                'message' => "The sector has been edited successfully.",
                'button' => "ok",
            ]);
        } else {
            return $this->render('crud/sector/edit-sector.html.twig', [
                'form' => $form->createView(),
                'sector' => $sector
            ]);
        }
    }

    /**
     * @Route("/crud/sectors/delete/{id}", name="delete-sector")
     */
    public function deleteSector(Security $security, Request $request, $id): Response
    {
        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user) {
            $role = $user->getIdRole()->getCodename();
        } else {
            return $this->redirectToRoute('login');
        }

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN') {
            return $this->redirectToRoute('login');
        }

        // Create the form
        $form = $this->createFormBuilder()
                        ->getForm();

        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
        $sector = $sector_repo->findOneById($id);

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid()){

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($sector);
            $entityManager->flush();

            return $this->RedirectToRoute('sectors', [
                'modal' => "Sector deleted",
                'message' => "The sector has been deleted successfully.",
                'button' => "ok",
            ]);
        } else {
            return $this->render('crud/sector/delete-sector.html.twig', [
                'form' => $form->createView(),
                'sector' => $sector
            ]);
        }
    }

    /**
     * @Route("/crud/sectors/{page}", defaults={"page"=1}, name="sectors")
     */
    public function sectorsIndex(Security $security, $page): Response
    {
        //checks if the page is a real number and converts
        if (!is_numeric($page)) {
            $page = 1;
        }
        $page = intval($page);

        //we get the entity repo and logged user
        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);

        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user) {
            $role = $user->getIdRole()->getCodename();
        } else {
            return $this->redirectToRoute('login');
        }

        //number of records, depends on role and if user, depends on user
        if ($role == 'ROLE_ADMIN') {
            $adapter = new ArrayAdapter($sector_repo->findAll());
        } else {
            $items = $userSector_repo->findSectorsByIdUser($user);
            $adapter = new ArrayAdapter($sector_repo->findAllByUserSectorIdArray($items));
        }

        //Creates the pagerfanta instance, sets max per page
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(10);

        //Limits the number of pages
        if ($page > $pagerfanta->getNbPages()) {
            $page = $pagerfanta->getNbPages();
        }
        $pagerfanta->setCurrentPage($page);

        //Here we do limit the pagination
        $minPagination = $page - 2;
        $maxPagination = $page + 2;
        if ($minPagination < 1) {
            $minPagination = 1;
            $maxPagination = 5;
        }
        if ($maxPagination > $pagerfanta->getNbPages()) {
            $maxPagination = $pagerfanta->getNbPages();
        }
        $useMin = false;
        $useMax = false;
        if ($minPagination > 1) {
            $useMin = true;
        }
        if ($maxPagination < $pagerfanta->getNbPages()) {
            $useMax = true;
        }

        $sectors = $sector_repo->findAll();

        return $this->render('crud/sector/sectors.html.twig', [
            'controller_name' => 'SectorController',
            'sectors' => $sectors,
            'pagerfanta' => $pagerfanta,
            'minPage' => $minPagination,
            'maxPage' => $maxPagination,
            'useMin' => $useMin,
            'useMax' => $useMax
        ]);
    }


}
