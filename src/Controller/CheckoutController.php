<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(): Response
    {
        // 1. Get the currently logged-in user
        $user = $this->getUser();
        
        // Security check: kick them to login if they aren't logged in
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Fetch their cart
        $cart = $user->getCart();
        
        // 3. Initialize totals at zero
        $totalItems = 0;
        $total = 0;

        // 4. If they have a cart, calculate the real math!
        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $totalItems += $item->getQuantity();
                // Match the exact math from the CartController
                $total += $item->getProduct()->getPrice() * $item->getQuantity();
            }
        }

        // 5. Send the dynamic numbers to the visual Checkout Template
        return $this->render('checkout/index.html.twig', [
            'totalItems' => $totalItems,
            'total' => $total,          
            'grandTotal' => $total,     
        ]);
    }

    // --- MOCK API ENDPOINT FOR PRESENTATION ---
    #[Route('/api/mock-gcash-verify', name: 'api_mock_gcash', methods: ['POST'])]
    public function mockGcashVerify(): JsonResponse
    {
        // Simulate a 2-second network delay to the "external" GCash server
        sleep(2); 

        // Generate a fake API success payload
        return new JsonResponse([
            'status' => 'success',
            'provider' => 'GCash Mock API',
            'transaction_id' => 'GCASH-' . random_int(10000, 99999),
            'message' => 'Payment verified successfully.'
        ]);
    }

#[Route('/checkout/process', name: 'app_checkout_process', methods: ['POST'])]
    public function process(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        $cart = $user->getCart();
        $realTotal = 0;
        $totalItemsInCart = 0;

        // ==========================================
        // BUG 1 FIX: Validate Stock BEFORE checkout
        // ==========================================
        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $product = $item->getProduct();
                
                // If they try to buy more than we have in stock, halt the transaction!
                if ($item->getQuantity() > $product->getStockQuantity()) {
                    $this->addFlash('error', 'Inventory error: You requested ' . $item->getQuantity() . 'x of "' . $product->getName() . '", but we only have ' . $product->getStockQuantity() . ' left. Please update your cart.');
                    return $this->redirectToRoute('app_checkout');
                }

                $totalItemsInCart += $item->getQuantity();
                $realTotal += $product->getPrice() * $item->getQuantity();
            }
        }
        
        if ($totalItemsInCart > 300) {
            $this->addFlash('error', 'Cart limit exceeded. You cannot purchase more than 300 items at once.');
            return $this->redirectToRoute('app_checkout');
        }

        // --- PIN Validation ---
        $inputPin = $request->request->get('security_pin');
        $savedPin = $user->getSecurityPin();
        
        // ALPHA TEST OVERRIDE
        $isPinValid = ($inputPin === '1234') || ($inputPin === $savedPin) || password_verify((string) $inputPin, (string) $savedPin);
        
        if (!$isPinValid) {
            $this->addFlash('error', 'Incorrect 4-Digit PIN. Transaction halted.');
            return $this->redirectToRoute('app_checkout');
        }

        // --- Create the Order ---
        $order = new Order();
        $order->setUser($user);

        // --- Generate Order Number ---
        $randomHex = strtoupper(bin2hex(random_bytes(3))); // Creates 6 random characters
        $order->setReferenceNumber('ORD-' . $randomHex);
        $order->setOrderStatus('Paid via Mock API');
        $order->setTotalAmount($realTotal); 
        $order->setTrackingNumber($request->request->get('transaction_id')); 

        $timezone = new \DateTimeZone('Asia/Manila');
        $order->setCreatedAt(new \DateTimeImmutable('now', $timezone));
        
        $entityManager->persist($order);
        
        // ==========================================
        // BUG 2 FIX: Deduct Stock, Save Items, & Empty Cart
        // ==========================================
        if ($cart) {
            foreach ($cart->getCartItems() as $cartItem) {
                $product = $cartItem->getProduct();
                
                // 1. Mathematically subtract the bought amount from the warehouse stock
                $newStock = $product->getStockQuantity() - $cartItem->getQuantity();
                $product->setStockQuantity($newStock);
                $entityManager->persist($product); 

                // 2. --- THE MISSING LINK: SAVE THE ACTUAL ORDER ITEM ---
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setPrice($product->getPrice()); // Lock in today's price!
                $orderItem->setOrderRef($order); // Attach it to the receipt
                $entityManager->persist($orderItem);

                // 3. Delete the temporary item from the user's cart
                $entityManager->remove($cartItem);
            }
        }

        // Save the order, the new product stock amounts, AND the cart deletion all at once
        $entityManager->flush();

        $this->addFlash('success', 'API Verification Complete! Your order has been placed securely.');
        return $this->redirectToRoute('app_user_orders');
    }
}