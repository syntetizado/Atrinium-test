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
use App\Entity\Company;
use App\Entity\User;
use App\Entity\UserSector;

// Import form type classes
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class CompanyController extends AbstractController
{
    /**
     * @Route("/crud/companies/new", name="create-company")
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

        $company_repo = $this->getDoctrine()->getRepository(Company::class);

        // Create the form
        $form = $this->createFormBuilder()
                        ->add('name', TextType::class)
                        ->add('phone', TextType::class)
                        ->add('email', EmailType::class)
                        ->add('sector', EntityType::class,[
                            'class' => Sector::class,
                            'choice_label' => 'name'/*,
                            'data' => $product->getCategory()*/
                        ])
                        ->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid()){

            $name = $form->get('name')->getData();
            $phone = $form->get('phone')->getData();
            $email = $form->get('email')->getData();
            $sector = $form->get('sector')->getData();

            if ($company_repo->findOneByName($name)) {
                return $this->render('crud/company/add-company.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That company name already exists.'
                ]);
            }

            $company = new Company();
            $company
                ->setName($name)
                ->setPhone($phone)
                ->setEmail($email)
                ->setIdSector($sector);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->RedirectToRoute('companies', ['success' => "The company '".$form->get('name')->getData()."' has been created."]);
        } else {
            return $this->render('crud/company/add-company.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/crud/companies/{page}", defaults={"page"=1}, name="companies")
     */
    public function companiesIndex(Security $security, $page): Response
    {
        //checks if the page is a real number and converts
        if (!is_numeric($page)) {
            $page = 1;
        }
        $page = intval($page);

        //we get the entity repo and logged user
        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);
        $company_repo = $this->getDoctrine()->getRepository(Company::class);

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
            $adapter = new ArrayAdapter($company_repo->findAll());
        } else {
            $sectors = $userSector_repo->findSectorsByIdUser($user);

            $companies_array = [];
            foreach ($sectors as $sector) {
                $companies = $company_repo->findByIdSector($sector->getIdSector()->getId());

                foreach ($companies as $company) {
                    $companies_array[] = $company;
                }
            }
            $adapter = new ArrayAdapter($companies_array);
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

        return $this->render('crud/company/companies.html.twig', [
            'controller_name' => 'SectorController',
            'pagerfanta' => $pagerfanta,
            'minPage' => $minPagination,
            'maxPage' => $maxPagination,
            'useMin' => $useMin,
            'useMax' => $useMax
        ]);
    }


}
