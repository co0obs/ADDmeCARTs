<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_SELLER')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Fetch all products owned by this specific seller
        $products = $entityManager->getRepository(Product::class)->findBy(['seller' => $user]);

        if ($request->isMethod('POST')) {
            $newStoreName = $request->request->get('storeName');
            $user->setStoreName($newStoreName);
            $entityManager->flush();

            $this->addFlash('success', 'Store Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'products' => $products, // <-- Pass the products to the template
        ]);
    }
}