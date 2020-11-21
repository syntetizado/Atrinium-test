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

        if ($user) {
            return $this->RedirectToRoute('index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        //$lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/auth/login.html.twig', [
            'error' => $error,
            //'lastUsername' => $lastUsername
        ]);
    } // FIN login

    /**
    * @Route("/register", name="register")
    */
    public function registerUser(UserPasswordEncoderInterface $encoder, Request $request){

		$user_repo = $this->getDoctrine()->getRepository(User::class);

		// Create the form
        $form = $this->createFormBuilder()
        				->add('email', EmailType::class,['label' => 'Email:'])
        				->add('password', PasswordType::class,['label' => 'Contraseña:'])
        				->add('confirmPassword', PasswordType::class,['label' => 'Repite la contraseña:'])
        				->add('username', TextType::class,['label' => 'Nombre:'])
        				->add('isAdmin', CheckboxType::class,['label' => 'Apellidos:', 'required' => 'false'])
          				->getForm();

        // Check form request
        $form->handleRequest($request);

        // Check form submission
        if ($form->isSubmitted() && $form->isValid()){

            $password = $form->get('password')->getData();
			$confirmPassword = $form->get('confirmPassword')->getData();
            $email = $form->get('email')->getData();
            $username = $form->get('username')->getData();
            $isAdmin = $form->get('isAdmin')->getData();

            if ($password != $confirmPassword) {
                return $this->render('home/auth/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'Passwords are not equal.'
                ]);
            }

            if ($user_repo->findOneByUsername($username)) {
                return $this->render('home/auth/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That Username already exists.'
                ]);
            }

            if ($user_repo->findOneByEmail($email)) {
                return $this->render('home/auth/register.html.twig', [
                    'form' => $form->createView(),
                    'error' => 'That Email already exists.'
                ]);
            }

            $role_repo = $this->getDoctrine()->getRepository(Role::class);
            if ($isAdmin) {
                $role = $role_repo->findOneByCodename('ROLE_ADMIN');
            } else {
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
        } else {
            return $this->render('home/auth/register.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    /**
    * @Route("/logout", name="logout")
    */
    public function logout(){
    }

    /**
    * @Route("/change-role", name="change-role")
    */
    public function changeRole(Security $security){
        //dont mind the security issue here. This is just for test purpose
        $user = $security->getUser();

        if ($user) {
            if ($security->getUser()->getIdRole()->getCodename() == 'ROLE_USER') {
                $role_repo = $this->getDoctrine()->getRepository(Role::class);
                $role = $role_repo->findOneByCodename('ROLE_ADMIN');

    			$user->setIdRole($role);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            } else {
                $role_repo = $this->getDoctrine()->getRepository(Role::class);
                $role = $role_repo->findOneByCodename('ROLE_USER');

                $user->setIdRole($role);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            }

            return $this->redirect('login');
        } else {
            return $this->redirect('login');
        }


    }
}
