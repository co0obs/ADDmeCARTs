<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
class OrderController extends AbstractController
{
    // THIS FIXES BUG 2: The Order History / Receipts Page
    #[Route('/my-orders', name: 'app_user_orders')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Fetch only the orders belonging to the logged-in user, sorted by newest first
        $orders = $entityManager->getRepository(Order::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        // Get reviews made by this user on completed orders
        $reviews = $entityManager->getRepository(Review::class)->findBy([
            'user' => $this->getUser(),
        ]);
        $reviewedProductIds = [];
        foreach ($reviews as $review) {
            $reviewedProductIds[] = $review->getProduct()->getId();
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'reviewedProductIds' => $reviewedProductIds,
        ]);
    }
}