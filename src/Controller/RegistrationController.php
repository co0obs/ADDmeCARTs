<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Using the correct Symfony 7 Attribute import!
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // ==========================================
            // 2. THE BULLETPROOF ROLE ASSIGNMENT
            // ==========================================
            // Try to get the dropdown choice from the form
            $selectedRole = $form->get('accountType')->getData();
            
            // Fallback: If the dropdown failed, was tampered with, or was empty, 
            // force them to be a safe Customer to prevent Ghost Admins.
            if (!$selectedRole) {
                $selectedRole = 'ROLE_CUSTOMER';
            }

            // Explicitly overwrite all previous roles with the safe choice
            $user->setRoles([$selectedRole]);

            // 3. Save the user to the SQLite database
            $entityManager->persist($user);
            $entityManager->flush();

            // 4. Redirect to login after successful registration
            return $this->redirectToRoute('app_login'); 
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}