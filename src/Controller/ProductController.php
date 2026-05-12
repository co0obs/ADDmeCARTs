<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\Notification;
use App\Entity\Order;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\WishlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProductController extends AbstractController
{
    // The catalog is public. No IsGranted needed here.
    #[Route('/products', name: 'app_product_catalog')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $keyword  = $request->query->get('q');
        $category = $request->query->get('category');
        $stars    = $request->query->getInt('stars');
        $sort     = $request->query->get('sort');
        $minPriceStr = $request->query->get('minPrice');
        $maxPriceStr = $request->query->get('maxPrice');

        $minPrice = is_numeric($minPriceStr) ? (float) $minPriceStr : null;
        $maxPrice = is_numeric($maxPriceStr) ? (float) $maxPriceStr : null;

        $products = $productRepository->searchAndFilter($keyword, $category, $stars, $sort, $minPrice, $maxPrice);

        return $this->render('product/index.html.twig', [
            'products'        => $products,
            'currentKeyword'  => $keyword,
            'currentCategory' => $category,
            'currentStars'    => $stars,
            'currentSort'     => $sort,
            'currentMinPrice' => $minPrice,
            'currentMaxPrice' => $maxPrice,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(Product $product, ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findByProduct($product->getId());
        $avgRating = $reviewRepository->getAverageRating($product->getId());

        return $this->render('product/show.html.twig', [
            'product'   => $product,
            'reviews'   => $reviews,
            'avgRating' => $avgRating,
        ]);
    }

    #[Route('/product/{id}/review', name: 'app_product_review', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submitReview(Product $product, Request $request, EntityManagerInterface $entityManager, ReviewRepository $reviewRepository): Response
    {
        $rating  = $request->request->getInt('rating');
        $comment = $request->request->get('comment');

        // Get order_id from POST or GET (query string)
        $orderId = $request->request->get('order_id') ?? $request->query->get('order_id');

        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'Please select a valid rating (1-5 stars).');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        // Verify the user has a completed order for this product
        $user = $this->getUser();
        $hasCompletedOrder = false;
        $order = null;

        if ($orderId) {
            $order = $entityManager->getRepository(Order::class)->find($orderId);
            if ($order && $order->getUser() === $user && $order->getOrderStatus() === 'Completed') {
                // Check if this product is in the order
                foreach ($order->getOrderItems() as $item) {
                    if ($item->getProduct()->getId() === $product->getId()) {
                        $hasCompletedOrder = true;
                        break;
                    }
                }
            }
        } else {
            // If no order_id provided, check if user has any completed order with this product
            $orders = $entityManager->getRepository(Order::class)->findBy([
                'user' => $user,
                'orderStatus' => 'Completed',
            ]);
            foreach ($orders as $completedOrder) {
                foreach ($completedOrder->getOrderItems() as $item) {
                    if ($item->getProduct()->getId() === $product->getId()) {
                        $hasCompletedOrder = true;
                        $order = $completedOrder;
                        break;
                    }
                }
                if ($hasCompletedOrder) break;
            }
        }

        if (!$hasCompletedOrder) {
            $this->addFlash('error', 'You can only review products from completed/delivered orders.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $review = new Review();
        $review->setUser($user);
        $review->setProduct($product);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setOrder($order);

        $entityManager->persist($review);

        // Notify the seller
        if ($product->getSeller()) {
            $notification = new Notification();
            $notification->setUser($product->getSeller());
            $notification->setMessage(sprintf('A customer left a %d-star review on %s.', $rating, $product->getName()));
            $notification->setLink($this->generateUrl('app_product_show', ['id' => $product->getId()]));
            $entityManager->persist($notification);
        }

        $entityManager->flush();

        // Update cached average rating in Product entity
        $newAvg = $reviewRepository->getAverageRating($product->getId());
        $product->setStarRating($newAvg);
        $entityManager->flush();

        $this->addFlash('success', 'Thank you for your review!');
        return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
    }

    // Only SELLERS can get into this method
    #[Route('/product/new', name: 'app_product_new')]
    #[IsGranted('ROLE_SELLER', message: 'Only registered sellers can add products.')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setSeller($this->getUser()); 
            
            $entityManager->persist($product);
            $entityManager->flush();

            // Flash a success message
            $this->addFlash('success', 'Your product was successfully added to your store!');

            return $this->redirectToRoute('app_product_catalog');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/product/{id}/edit', name: 'app_product_edit')]
    #[IsGranted('ROLE_SELLER')]
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager, WishlistRepository $wishlistRepository): Response
    {
        // SECURITY CHECK
        if ($product->getSeller() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit a product you do not own.');
        }

        $originalPrice = $product->getPrice();
        $originalSalePrice = $product->getSalePrice();
        $originalStock = $product->getStockQuantity();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for price drop or restock to notify wishlist users
            $isSale = ($product->getSalePrice() !== null && $product->getSalePrice() !== $originalSalePrice && ($originalSalePrice === null || $product->getSalePrice() < $originalSalePrice));
            $isPriceDrop = ($product->getPrice() < $originalPrice);
            $isRestock = ($originalStock === 0 && $product->getStockQuantity() > 0);

            if ($isSale || $isPriceDrop || $isRestock) {
                $wishlists = $wishlistRepository->findBy(['product' => $product]);
                foreach ($wishlists as $wishlist) {
                    $notification = new Notification();
                    $notification->setUser($wishlist->getUser());
                    
                    if ($isRestock) {
                        $notification->setMessage(sprintf('Back in Stock Alert: %s is now available!', $product->getName()));
                    } else {
                        $notification->setMessage(sprintf('Price Drop Alert: %s is now on sale for ₱%s!', $product->getName(), number_format($product->getEffectivePrice(), 2)));
                    }
                    
                    $notification->setLink($this->generateUrl('app_product_show', ['id' => $product->getId()]));
                    $entityManager->persist($notification);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/product/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SELLER')]
    public function delete(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK
        if ($product->getSeller() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete a product you do not own.');
        }

        // CSRF Token validation for secure deletion
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/review/{id}/reply', name: 'app_review_reply', methods: ['POST'])]
    #[IsGranted('ROLE_SELLER')]
    public function replyToReview(Review $review, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $review->getProduct();
        if ($product->getSeller() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only reply to reviews on your own products.');
        }

        $replyText = $request->request->get('sellerReply');
        if ($replyText) {
            $review->setSellerReply($replyText);
            $review->setRepliedAt(new \DateTime('now', new \DateTimeZone('Asia/Manila')));
            
            // Notify the customer
            $notification = new Notification();
            $notification->setUser($review->getUser());
            $notification->setMessage(sprintf('The seller replied to your review on %s.', $product->getName()));
            $notification->setLink($this->generateUrl('app_product_show', ['id' => $product->getId()]));
            
            $entityManager->persist($notification);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your reply has been posted.');
        }

        return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
    }
}