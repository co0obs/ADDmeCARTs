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
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Fetch products if user is a seller
        $products = [];
        if (in_array('ROLE_SELLER', $user->getRoles())) {
            $products = $entityManager->getRepository(Product::class)->findBy(['seller' => $user]);
        }

        if ($request->isMethod('POST')) {
            // Handle store name (for sellers)
            $storeName = $request->request->get('storeName');
            if ($storeName !== null) {
                $user->setStoreName($storeName);
            }

            // Handle addresses
            $address1 = $request->request->get('address1');
            $address2 = $request->request->get('address2');
            $address3 = $request->request->get('address3');
            $defaultAddress = $request->request->get('defaultAddress');

            $user->setAddress1($address1 ?: null);
            $user->setAddress2($address2 ?: null);
            $user->setAddress3($address3 ?: null);

            // Set default address index (0, 1, or 2)
            if ($defaultAddress !== null && is_numeric($defaultAddress)) {
                $user->setDefaultAddressIndex((int) $defaultAddress);
            } else {
                $user->setDefaultAddressIndex(0);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'products' => $products,
        ]);
    }
}