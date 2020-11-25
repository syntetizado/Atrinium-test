<?php

namespace App\Controller;

// Basic controller classes
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// Import HTTP request and response handlers
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// Import needed entities
use App\Entity\User;
use App\Entity\UserSector;
use App\Entity\Sector;
use App\Entity\Company;
use App\Entity\Role;

// Import necesary classes for login and security
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;

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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

// Import needed classes for using pagerfanta
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

// Importamos la clase Constraints para realizar validaciones sobre el formulario
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    /**
    * @Route("/login", name="login")
    */
    public function login(AuthenticationUtils $authenticationUtils, Security $security)
    {
        $user = $security->getUser();

        if ($user)
        {
            return $this->RedirectToRoute('index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        //$lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/auth/login.html.twig', [
            'error' => $error,
            //'lastUsername' => $lastUsername
        ]);
    } //END login(AuthenticationUtils $authenticationUtils, Security $security)

    /**
    * @Route("/register", name="register")
    */
    public function registerUser(UserPasswordEncoderInterface $encoder, Request $request)
    {
		$user_repo = $this->getDoctrine()->getRepository(User::class);

		// Create the form
        $form = $this->createFormBuilder()
        				->add('email', EmailType::class,[
                            'label' => 'Email:'
                        ])
        				->add('password', PasswordType::class,[
                            'label' => 'Contraseña:'
                        ])
        				->add('confirmPassword', PasswordType::class,[
                            'label' => 'Repite la contraseña:'
                        ])
        				->add('username', TextType::class,[
                            'label' => 'Nombre:'
                        ])
        				->add('isAdmin', CheckboxType::class,[
                            'label' => 'Apellidos:',
                            'required' => 'false'
                        ])
          				->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            //we get the data from the registration form
            $password = $form->get('password')->getData();
			$confirmPassword = $form->get('confirmPassword')->getData();
            $email = $form->get('email')->getData();
            $username = $form->get('username')->getData();
            $isAdmin = $form->get('isAdmin')->getData();

            //check if password is confirmed successfully
            if ($password != $confirmPassword)
            {
                return $this->render('home/auth/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'Passwords are not equal.'
                ]);
            }

            //check if username exists
            if ($user_repo->findOneByUsername($username))
            {
                return $this->render('home/auth/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That Username already exists.'
                ]);
            }

            //check if email exists
            if ($user_repo->findOneByEmail($email))
            {
                return $this->render('home/auth/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That Email already exists.'
                ]);
            }

            $role_repo = $this->getDoctrine()->getRepository(Role::class);
            if ($isAdmin)
            {
                $role = $role_repo->findOneByCodename('ROLE_ADMIN');
            }
            else
            {
                $role = $role_repo->findOneByCodename('ROLE_USER');
            }

            $user = new User();
			$user
				->setEmail($email)
				->setUsername($username)
				->setPassword($password)
				->setIsActive(1)
				->setIdRole($role);

            $encodedPw = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($encodedPw);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->RedirectToRoute('login', ['success' => "The user '".$form->get('username')->getData()."' has been created. Please log-in now."]);
        }
        else
        {
            return $this->render('home/auth/register.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }//END registerUser(UserPasswordEncoderInterface $encoder, Request $request)

    /**
    * @Route("/logout", name="logout")
    */
    public function logout(){
    }

    /**
    * @Route("/change-role", name="change-role")
    */
    public function changeRole(Security $security)
    {
        //dont mind the security issue here. This is just for test purpose
        $user = $security->getUser();

        if ($user)
        {
            if ($security->getUser()->getIdRole()->getCodename() == 'ROLE_USER')
            {
                $role_repo = $this->getDoctrine()->getRepository(Role::class);
                $role = $role_repo->findOneByCodename('ROLE_ADMIN');

    			$user->setIdRole($role);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            }
            else
            {
                $role_repo = $this->getDoctrine()->getRepository(Role::class);
                $role = $role_repo->findOneByCodename('ROLE_USER');

                $user->setIdRole($role);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            }

            return $this->redirect('login');
        }
        else
        {
            return $this->redirect('login');
        }
    }

    /**
    * @Route("/profile", name="profile")
    */
    public function userProfile(Security $security)
    {
        //initialize vars
        $sectors = []; $companies = []; $users = [];
        $companyDots = false; $sectorDots = false; $entriesLimit = 10;

        //check user is logged
        $user = $security->getUser();
        if (!$user)
        {
            return $this->RedirectToRoute('index', [
                'modal' => "Log first",
                'message' => "In order to see your profile, you must be logged in.",
                'button' => "ok",
            ]);
        }

        //we get the needed repos
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);
        $company_repo = $this->getDoctrine()->getRepository(Company::class);

        /*need this for checking if entries exceed entries limited by the variable
        and also get a nice visual representation on twig*/
        $sector_count = $userSector_repo->countByIdUser($user);
        if ($sector_count > $entriesLimit) {
            $sectorDots = true;
        }

        //get limited entries
        $sectors = $userSector_repo->findByIdUserLimitByInt($user,$entriesLimit);

        //we check sectors again to get all companies
        $allUserSectors = $userSector_repo->findByIdUser($user);
        $stop = false;
        foreach ($allUserSectors as $sector)
        {
            //if we stopped getting entries, it breaks the loop
            if (!$stop) {
                //this is a loop through a sector to get the companies
                $sectorCompanies = $company_repo->findByIdSector($sector->getIdSector()->getId());
                foreach ($sectorCompanies as $company)
                {
                    //if we are over the limit, it notices to twig, and then break
                    if (count($companies) >= $entriesLimit) {
                        $stop = true;
                        $companyDots = true;
                        break;
                    }
                    //if not, we add that company
                    if (!$stop) {
                        $companies[] = $company;
                    }
                }
            } else {
                break;
            }
        }

        //and we render the template
        return $this->render('home/auth/user-profile.html.twig',[
                'sectors' => $sectors,
                'companies' => $companies,
                'users' => $users,
                'sectorDots' => $sectorDots,
                'companyDots' => $companyDots,
            ]);

    } // END profile

    /**
    * @Route("/admin/user/create-user", name="admin-create-user")
    */
    public function adminCreateUser(UserPasswordEncoderInterface $encoder, Security $security, Request $request){
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

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN')
        {
            return $this->redirectToRoute('login');
        }

        //we get first the repos
        $role_repo = $this->getDoctrine()->getRepository(Role::class);
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);

        //then get the data we need
        $roles = $role_repo->findAll(); //get the roles
        $sectors = $sector_repo->findAll(); //get all sectors

        // Create the form
        $form = $this->createFormBuilder()
        				->add('email', EmailType::class,[
                            'label' => 'Email:',
                            'attr' => [
                                'placeholder' => 'email@example.com'
                            ]
                        ])
        				->add('password', PasswordType::class,[
                            'label' => 'Password:',
                            'attr' => [
                                'placeholder' => 'password'
                            ]
                        ])
        				->add('username', TextType::class,[
                            'label' => 'Username:',
                            'attr' => [
                                'placeholder' => 'username'
                            ]
                        ])
        				->add('isActive', CheckboxType::class,[
                            'label' => 'Is Active:',
                            'required' => 'false'
                        ])
                        ->add('role', EntityType::class,[
                            'class' => Role::class,
                            'choices' => $roles,
                            'choice_label' => 'name'
                        ])
                        ->add('data_in_sectors', HiddenType::class)
          				->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            //first we obtain the data in the form
            $username = $form->get('username')->getData();
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData();
            $isActive = $form->get('isActive')->getData();
            $role = $form->get('role')->getData();
            $dataInSectors = $form->get('data_in_sectors')->getData();

            dd($dataInSectors);

            //if changed, we will check if it exists on other entries
            if ($user_repo->findOneByUsername($username))
            {
                return $this->render('home/admin/create-user.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That username already exists.',
                    'user' => $user,
                    'sectors' => $sectors
                ]);
            }

            //if changed, we will check if it exists on other entries
            if ($user_repo->findOneByEmail($email))
            {
                return $this->render('home/admin/create-user.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That email already exists.',
                    'user' => $user,
                    'sectors' => $sectors
                ]);
            }
            $user = new User();

            $user->setUsername($username);
            $user->setEmail($email);
            $user->setIsActive($isActive);
            $user->setIdRole($role);

            if ($password)
            {
                $encodedPw = $encoder->encodePassword($user, $password);
                $user->setPassword($encodedPw);
            }

            //we get all the id listed on the select list
            $dataInSectors = substr($dataInSectors, 0, -1);
            $selectedSectors = explode(",", $dataInSectors);

            //get the entity manager
            $entityManager = $this->getDoctrine()->getManager();

            //get each sector from its id
            foreach ($selectedSectors as $idSector)
            {
                $sector = $sector_repo->findOneById($idSector);

                if ($sector)
                {
                    //then if it's not already connected to user
                    if (!$userSector_repo->findOneByUserAndSector($user, $sector))
                    {
                        //register the entry
                        $userSector = new UserSector();
                        $userSector->setIdSector($sector);
                        $userSector->setIdUser($user);

                        $entityManager->persist($userSector);
                    }
                }

            }

            $userBelongedSectors = $userSector_repo->findByIdUser($user);

            $test = []; $count = 0;
            foreach ($userBelongedSectors as $userBelongedSector)
            {
                $found= false;
                foreach ($selectedSectors as $selectedSectorId)
                {
                    if ($userBelongedSector->getIdSector()->getId() == $selectedSectorId)
                    {
                        $found= true;
                        $count++;
                        $test[$count] = 'belongId: ' . $userBelongedSector->getId() . 'selectedId: ' . $selectedSectorId;
                    }
                }

                if (!$found)
                {
                    $entityManager->remove($userBelongedSector);
                    $entityManager->flush();
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->RedirectToRoute('admin-users', [
                'modal' => "User created",
                'message' => "The user '$username' has been created successfully.",
                'button' => "ok",
            ]);
        }
        else
        {
            return $this->render('home/admin/create-user.html.twig', [
                'form' => $form->createView(),
                'sectors' => $sectors
            ]);
        }
    }

    /**
    * @Route("/admin/user/edit-user/{id}", name="admin-edit-user")
    */
    public function adminEditUser(UserPasswordEncoderInterface $encoder, Security $security, Request $request, $id){
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

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN')
        {
            return $this->redirectToRoute('login');
        }

        //we get first the repos
        $role_repo = $this->getDoctrine()->getRepository(Role::class);
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $sector_repo = $this->getDoctrine()->getRepository(Sector::class);
        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);

        //then get the data we need
        $roles = $role_repo->findAll(); //get the roles
        $sectors = $sector_repo->findAll(); //get all sectors
        $user = $user_repo->findOneById($id); //our user requested

        $sectorsOwned = $userSector_repo->findByIdUser($user);

        if (!$user) {
            return $this->RedirectToRoute('admin-users', [
                'modal' => "Not found",
                'message' => "This user doesn't exists",
                'button' => "ok",
            ]);
        }

        // Create the form
        $form = $this->createFormBuilder()
        				->add('email', EmailType::class,[
                            'label' => 'Email:',
                            'data' => $user->getEmail(),
                            'attr' => [
                                'placeholder' => 'email@example.com'
                            ]
                        ])
        				->add('password', PasswordType::class,[
                            'label' => 'Password:',
                            'required' => 'false',
                            'attr' => [
                                'placeholder' => 'new password? fill this'
                            ]
                        ])
        				->add('username', TextType::class,[
                            'label' => 'Username:',
                            'data' => $user->getUsername(),
                            'attr' => [
                                'placeholder' => 'username'
                            ]
                        ])
        				->add('isActive', CheckboxType::class,[
                            'label' => 'Is Active:',
                            'required' => 'false',
                            'data' => $user->getIsActive(),
                        ])
                        ->add('role', EntityType::class,[
                            'class' => Role::class,
                            'choices' => $roles,
                            'data' => $user->getIdRole(),
                            'choice_label' => 'name'
                        ])
                        ->add('data_in_sectors', HiddenType::class)
          				->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            //first we obtain the data in the form
            $username = $form->get('username')->getData();
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData();
            $isActive = $form->get('isActive')->getData();
            $role = $form->get('role')->getData();
            $dataInSectors = $form->get('data_in_sectors')->getData();

            if  (
                $user->getUsername() == $username &&
                $user->getEmail() == $email &&
                $user->getIsActive() == $isActive &&
                $user->getIdRole()->getId() == $role->getId() &&
                !$password &&
                !$dataInSectors
                )
            {
                return $this->RedirectToRoute('admin-users', [
                    'modal' => "Nothing changed",
                    'message' => "The user '".$form->get('username')->getData()."' hasn't changed.",
                    'button' => "ok",
                ]);
            }

            //check if the name changed
            if ($user->getUsername() != $username)
            {
                //if changed, we will check if it exists on other entries
                if ($user_repo->findOneByUsername($username))
                {
                    return $this->render('home/admin/edit-user.html.twig', [
                        'form' => $form->createView(),
                        'error' => 'That username already exists.',
                        'user' => $user,
                        'sectors' => $sectors,
                        'sectorsOwned' => $sectorsOwned
                    ]);
                }
            }

            //check if the name changed
            if ($user->getEmail() != $email)
            {
                //if changed, we will check if it exists on other entries
                if ($user_repo->findOneByEmail($email))
                {
                    return $this->render('home/admin/edit-user.html.twig', [
                        'form' => $form->createView(),
                        'error' => 'That email already exists.',
                        'user' => $user,
                        'sectors' => $sectors,
                        'sectorsOwned' => $sectorsOwned
                    ]);
                }
            }

            $user->setUsername($username);
            $user->setEmail($email);
            $user->setIsActive($isActive);
            $user->setIdRole($role);

            if ($password)
            {
                $encodedPw = $encoder->encodePassword($user, $password);
                $user->setPassword($encodedPw);
            }

            //we get all the id listed on the select list
            $dataInSectors = substr($dataInSectors, 0, -1);
            $selectedSectors = explode(",", $dataInSectors);

            //get the entity manager
            $entityManager = $this->getDoctrine()->getManager();

            //get each sector from its id
            foreach ($selectedSectors as $idSector)
            {
                $sector = $sector_repo->findOneById($idSector);

                if ($sector)
                {
                    //then if it's not already connected to user
                    if (!$userSector_repo->findOneByUserAndSector($user, $sector))
                    {
                        //register the entry
                        $userSector = new UserSector();
                        $userSector->setIdSector($sector);
                        $userSector->setIdUser($user);

                        $entityManager->persist($userSector);
                    }
                }

            }

            $userBelongedSectors = $userSector_repo->findByIdUser($user);

            $test = []; $count = 0;
            foreach ($userBelongedSectors as $userBelongedSector)
            {
                $found= false;
                foreach ($selectedSectors as $selectedSectorId)
                {
                    if ($userBelongedSector->getIdSector()->getId() == $selectedSectorId)
                    {
                        $found= true;
                        $count++;
                        $test[$count] = 'belongId: ' . $userBelongedSector->getId() . 'selectedId: ' . $selectedSectorId;
                    }
                }

                if (!$found)
                {
                    $entityManager->remove($userBelongedSector);
                    $entityManager->flush();
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->RedirectToRoute('admin-users', [
                'modal' => "User edited",
                'message' => "The user '$username' has been edited successfully.",
                'button' => "ok",
            ]);
        }
        else
        {
            return $this->render('home/admin/edit-user.html.twig', [
                'form' => $form->createView(),
                'user' => $user,
                'sectors' => $sectors,
                'sectorsOwned' => $sectorsOwned
            ]);
        }
    }

    /**
    * @Route("/admin/user/delete-user/{id}", name="admin-delete-user")
    */
    public function adminDeleteUser(Security $security, Request $request, $id){
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

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN')
        {
            return $this->redirectToRoute('login');
        }

        // Create the form
        $form = $this->createFormBuilder()
                        ->getForm();

        $userSector_repo = $this->getDoctrine()->getRepository(UserSector::class);
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneById($id);

        if (!$user) {
            return $this->RedirectToRoute('admin-users', [
                'modal' => "Not found",
                'message' => "This user doesn't exists",
                'button' => "ok",
            ]);
        }

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();

            $userSectors = $userSector_repo->findByIdUser($user);
            foreach ($userSectors as $sector) {
                $entityManager->remove($sector);
                $entityManager->flush();
            }

            $userUsername = $user->getUsername();

            $entityManager->remove($user);
            $entityManager->flush();

            return $this->RedirectToRoute('sectors', [
                'modal' => "User deleted",
                'message' => "The user '$userUsername' has been deleted successfully.",
                'button' => "ok",
            ]);
        }
        else
        {
            return $this->render('home/admin/delete-user.html.twig', [
                'form' => $form->createView(),
                'user' => $user
            ]);
        }
    }

    /**
    * @Route("/admin/users/{page}", defaults={"page"=1}, name="admin-users")
    */
    public function adminUsersIndex(Security $security, $page){
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

        //stops the method if it's not admin
        if ($role != 'ROLE_ADMIN')
        {
            return $this->RedirectToRoute('index', [
                'modal' => "Permission denied",
                'message' => "Only admins can administrate users",
                'button' => "ok",
            ]);
        }

        //checks if the page is a real number and converts
        if (!is_numeric($page))
        {
            $page = 1;
        }
        $page = intval($page);

        $users_repo = $this->getDoctrine()->getRepository(User::class);
        $users = $users_repo->findAll();

        //we put the entities into the array adapter so pagerfanta can use it
        $adapter = new ArrayAdapter($users);

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

        //and we render the template
        return $this->render('home/admin/users.html.twig',[
                'controller_name' => 'UserController',
                'pagerfanta' => $pagerfanta,
                'minPage' => $minPagination,
                'maxPage' => $maxPagination,
                'useMin' => $useMin,
                'useMax' => $useMax
            ]);
    }





}
