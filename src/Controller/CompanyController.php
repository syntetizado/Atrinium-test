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

// Import needed classes for using pagerfanta
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class CompanyController extends AbstractController
{

    private function checkIfUserOwnsCompany(Security $security, $id) :bool
    {
        //we get the entity repo and logged user
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);
        $company_repo = $this->getDoctrine()->getRepository(Company::class);

        //We get the user logged in
        $user = $security->getUser();

        $sectors = $userSector_repo->findByIdUser($user);

        $companies_array = [];
        foreach ($sectors as $sector)
        {
            $companies = $company_repo->findByIdSector($sector->getIdSector()->getId());

            foreach ($companies as $company)
            {
                $companies_array[] = $company;
            }
        }

        $found = false;
        foreach ($companies_array as $company)
        {
            if ($company->getId() == $id)
            {
                $found = true;
            }
        }

        return $found;
    }

    /**
     * @Route("/crud/company/new", name="create-company")
     */
    public function createCompany(Security $security, Request $request): Response
    {
        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user)
        {
            $role = $user->getIdRole()->getCodename();
        }
        else
        {
            return $this->redirectToRoute('login');
        }

        //we get common repos of admin and user
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);

        //gets the sectors depending on roles
        if ($role != 'ROLE_ADMIN') //if user
        {
            //we get the repos we need
            $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);

            //find all sectors this user belongs
            $userSectors = $userSector_repo->findByIdUser($user);

            //get their names and put them into array
            $sectors_entities = [];
            foreach ($userSectors as $sector)
            {
                $sectors_entities []= $sector->getIdSector();
            }
        }
        else //if admin
        {
            //We get all sectors
            $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
            $sectors_entities = $sector_repo->findAll();
        }

        // Create the form
        $form = $this->createFormBuilder()
                        ->add('name', TextType::class)
                        ->add('phone', TextType::class)
                        ->add('email', EmailType::class)
                        ->add('sector', EntityType::class,[
                            'class' => Sector::class,
                            'choices' => $sectors_entities,
                            'choice_label' => 'name'
                        ])
                        ->getForm();

        // Check form request
        $form->handleRequest($request);



        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            $name = $form->get('name')->getData();
            $phone = $form->get('phone')->getData();
            $email = $form->get('email')->getData();
            $sector = $form->get('sector')->getData();

            if (!$userSector_repo->findByUserAndSector($user, $sector) && $user->getIdRole()->getCodename() == 'ROLE_USER') {
                return $this->render('crud/company/add-company.html.twig', [
                    'form' => $form->createView(),
                    'error' => "You don't belong to that sector."
                ]);
            }

            $company_repo = $this->getDoctrine()->getRepository(Company::class);

            if ($company_repo->findOneByName($name))
            {
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

            return $this->RedirectToRoute('companies', [
                'success' => "The company '".$form->get('name')->getData()."' has been created."
            ]);
        }
        else
        {
            return $this->render('crud/company/add-company.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/crud/company/edit/{id}", name="edit-company")
     */
    public function editCompany(Security $security, Request $request, $id): Response
    {
        //this repo is common for both admin and user
        $company_repo = $this->getDoctrine()->getRepository(Company::class);

        //we get the company from the ID
        $company = $company_repo->findOneById($id);

        if (!$company) {
            return $this->RedirectToRoute('companies', [
                'modal' => "Not found",
                'message' => "That company doesn't exists",
                'button' => "ok",
            ]);
        }

        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user)
        {
            $role = $user->getIdRole()->getCodename();
        }
        else
        {
            return $this->redirectToRoute('login');
        }

        //gets the sectors depending on roles
        if ($role != 'ROLE_ADMIN') //if user
        {
            //stops if user doesnt have that company
            if (!$this->checkIfUserOwnsCompany($security, $id))
            {
                return $this->RedirectToRoute('companies', [
                    'modal' => "Permission denied",
                    'message' => "That company is not yours",
                    'button' => "ok",
                ]);
            }

            //Now that we are allowed, we get the repos we need
            $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);

            $userSectors = $userSector_repo->findByIdUser($user);

            $sectors_entities = [];
            foreach ($userSectors as $sector)
            {
                $sectors_entities []= $sector->getIdSector();
            }
        }
        else //if admin
        {
            //We get all sectors
            $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
            $sectors_entities = $sector_repo->findAll();
        }

        // Create the form
        $form = $this->createFormBuilder()
                        ->add('name', TextType::class,[
                            'label' => 'Name:',
                            'data' => $company->getName(),
                        ])
                        ->add('phone', TextType::class,[
                            'data' => $company->getPhone(),
                        ])
                        ->add('email', EmailType::class,[
                            'data' => $company->getEmail(),
                        ])
                        ->add('sector', EntityType::class,[
                            'class' => Sector::class,
                            'choices' => $sectors_entities,
                            'data' => $company->getIdSector(),
                            'choice_label' => 'name'
                        ])
                        ->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            //first we obtain the data in the form
            $name = $form->get('name')->getData();
            $phone = $form->get('phone')->getData();
            $email = $form->get('email')->getData();
            $sector = $form->get('sector')->getData();

            if  (
                $company->getName() == $name &&
                $company->getPhone() == $phone &&
                $company->getEmail() == $email &&
                $company->getIdSector()->getId() == $sector->getId()
                )
            {
                return $this->RedirectToRoute('companies', [
                    'modal' => "Nothing changed",
                    'message' => "The company '".$form->get('name')->getData()."' hasn't changed.",
                    'button' => "ok",
                ]);
            }

            //check if the name changed
            if ($company->getName() != $name)
            {
                //if changed, we will check if it exists on other entries
                if ($company_repo->findOneByName($name))
                {
                    return $this->render('crud/company/edit-company.html.twig', [
                        'form' => $form->createView(),
                        'error' => 'That company name already exists.',
                        'company' => $company
                    ]);
                }
            }

            $company->setName($name);
            $company->setPhone($phone);
            $company->setEmail($email);
            $company->setIdSector($sector);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->RedirectToRoute('companies', [
                'modal' => "Company edited",
                'message' => "The company has been edited successfully.",
                'button' => "ok",
            ]);
        }
        else
        {
            return $this->render('crud/company/edit-company.html.twig', [
                'form' => $form->createView(),
                'company' => $company
            ]);
        }
    }

    /**
     * @Route("/crud/company/delete/{id}", name="delete-company")
     */
    public function deleteCompany(Security $security, Request $request, $id): Response
    {
        //this repo is common for both admin and user
        $company_repo = $this->getDoctrine()->getRepository(Company::class);

        //we get the company from the ID
        $company = $company_repo->findOneById($id);

        if (!$company) {
            return $this->RedirectToRoute('companies', [
                'modal' => "Not found",
                'message' => "That company doesn't exists",
                'button' => "ok",
            ]);
        }

        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user)
        {
            $role = $user->getIdRole()->getCodename();
        }
        else
        {
            return $this->redirectToRoute('login');
        }

        //gets the sectors depending on roles
        if ($role != 'ROLE_ADMIN') //if user
        {
            //stops if user doesnt have that company
            if (!$this->checkIfUserOwnsCompany($security, $id))
            {
                return $this->RedirectToRoute('companies', [
                    'modal' => "Permission denied",
                    'message' => "That company is not yours",
                    'button' => "ok",
                ]);
            }
        }

        // Create the form
        $form = $this->createFormBuilder()
                        ->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            $companyName = $company->getName();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($company);
            $entityManager->flush();

            return $this->RedirectToRoute('companies', [
                'modal' => "Company deleted",
                'message' => "The company '$companyName' has been deleted successfully.",
                'button' => "ok",
            ]);
        }
        else
        {
            return $this->render('crud/company/delete-company.html.twig', [
                'form' => $form->createView(),
                'company' => $company
            ]);
        }
    }

    /**
     * @Route("/crud/companies/{page}", defaults={"page"=1}, name="companies")
     */
    public function companiesIndex(Security $security, Request $request, $page): Response
    {
        //checks if the page is a real number and converts
        if (!is_numeric($page))
        {
            $page = 1;
        }
        $page = intval($page);

        //we get the entity repo and logged user
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);
        $company_repo = $this->getDoctrine()->getRepository(Company::class);
        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);

        //We get the user logged in
        $user = $security->getUser();

        //check for user and get his role, redirect if no user is logged
        if ($user)
        {
            $role = $user->getIdRole()->getCodename();
        }
        else
        {
            return $this->redirectToRoute('login');
        }

        //we get the sectors depending on user
        if ($role == 'ROLE_USER')
        {
            $sectors = $userSector_repo->findByIdUser($user);

            $sectorArray = [];
            foreach ($sectors as $sector) {
                $sectorArray [] = $sector;
            }
        }
        else if ($role == 'ROLE_ADMIN')
        {
            $sectorArray = $sector_repo->findAll();
        }

        if (!$this->get('session')->get('nameFilter')) {
            $nameFilter = "";
        } else {
            $nameFilter = $this->get('session')->get('nameFilter');
        }

        if (!$this->get('session')->get('sectorFilter')) {
            $sectorFilter = "";
        } else {
            $sectorFilter = $this->get('session')->get('sectorFilter');
        }

        //we get the records, they depend on role. and if user, depends on user id
        if ($role == 'ROLE_ADMIN')
        {
            if ( $sectorFilter == '' )
            {
                $companies_array = $company_repo->findAll();
            }
            else
            {
                $companies_array = $company_repo->findByIdSector($sectorFilter);
            }
        }
        else
        {
            $sectors = $userSector_repo->findByIdUser($user);

            if ($sectorFilter == null || $sectorFilter == '' || $sectorFilter == 0)
            {
                $companies_array = [];
                foreach ($sectors as $sector)
                {
                    $companies = $company_repo->findByIdSector($sector->getIdSector()->getId());

                    foreach ($companies as $company)
                    {
                        $companies_array[] = $company;
                    }
                }
            }
            else
            {
                if ($userSector_repo->findOneByUserAndSector($user, $sectorFilter)) {
                    $companies_array = $company_repo->findByIdSector($sector->getIdSector()->getId());
                }
            }
        }

        if ($nameFilter == "" || $nameFilter == null)
        {
            $filteredCompanies = $companies_array;
        }
        else
        {
            //now we filter the companies
            $filteredCompanies = [];
            foreach ($companies_array as $company )
            {
                //check if the company name has the string on our filter
                $companyName = $company->getName();
                if (stristr($companyName, $nameFilter))
                {
                    //and adds it to the filter registry
                    $filteredCompanies [] = $company;
                }
            }
        }

        $adapter = new ArrayAdapter($filteredCompanies);

        //Creates the pagerfanta instance, sets max per page
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(10);

        //Limits the number of pages
        if ($page > $pagerfanta->getNbPages())
        {
            $page = $pagerfanta->getNbPages();
        }
        $pagerfanta->setCurrentPage($page);

        //Here we do limit the pagination
        $minPagination = $page - 2;
        $maxPagination = $page + 2;

        if ($minPagination < 1)
        {
            $minPagination = 1;
            $maxPagination = 5;
        }

        if ($maxPagination > $pagerfanta->getNbPages())
        {
            $maxPagination = $pagerfanta->getNbPages();
        }

        $useMin = false;
        $useMax = false;

        if ($minPagination > 1)
        {
            $useMin = true;
        }

        if ($maxPagination < $pagerfanta->getNbPages())
        {
            $useMax = true;
        }

        return $this->render('crud/company/companies.html.twig', [
            'controller_name' => 'SectorController',
            'pagerfanta' => $pagerfanta,
            'minPage' => $minPagination,
            'maxPage' => $maxPagination,
            'useMin' => $useMin,
            'useMax' => $useMax,
            'sectors' => $sectorArray
        ]);
    }

    /**
     * @Route("/ajax-session", name="ajax")
     */
    public function ajaxFilter(Request $request): Response
    {
        $this->get('session')->set('nameFilter', $request->get('nameFilter'));
        $this->get('session')->set('sectorFilter', $request->get('sectorFilter'));

        return new Response();
    }
}
