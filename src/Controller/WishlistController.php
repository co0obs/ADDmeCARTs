<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Wishlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
#[Route('/wishlist')]
class WishlistController extends AbstractController
{
    #[Route('', name: 'app_wishlist_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $wishlists = $entityManager->getRepository(Wishlist::class)->findBy(['user' => $this->getUser()]);

        return $this->render('wishlist/index.html.twig', [
            'wishlists' => $wishlists,
        ]);
    }

    #[Route('/add/{id}', name: 'app_wishlist_add', methods: ['POST', 'GET'])]
    public function add(Product $product, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();
        
        // Check if already in wishlist
        $existing = $entityManager->getRepository(Wishlist::class)->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        if (!$existing) {
            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $wishlist->setProduct($product);
            $entityManager->persist($wishlist);
            $entityManager->flush();
            $this->addFlash('success', 'Added to your wishlist!');
        } else {
            $this->addFlash('info', 'Item is already in your wishlist.');
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_product_catalog'));
    }

    #[Route('/remove/{id}', name: 'app_wishlist_remove', methods: ['POST', 'GET'])]
    public function remove(Wishlist $wishlist, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Ensure user owns this wishlist item
        if ($wishlist->getUser() === $this->getUser()) {
            $entityManager->remove($wishlist);
            $entityManager->flush();
            $this->addFlash('success', 'Removed from your wishlist.');
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_wishlist_index'));
    }
}
