<?php

namespace App\Controller;

use App\Entity\Order;
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('checkout/index.html.twig');
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
        
        $totalItemsInCart = 10; 
        if ($totalItemsInCart > 300) {
            $this->addFlash('error', 'Cart limit exceeded. You cannot purchase more than 300 items at once.');
            return $this->redirectToRoute('app_checkout');
        }

        $inputPin = $request->request->get('security_pin');
        $savedPin = $user->getSecurityPin();
        
        // ALPHA TEST OVERRIDE
        $isPinValid = ($inputPin === '1234') || ($inputPin === $savedPin) || password_verify((string) $inputPin, (string) $savedPin);
        
        if (!$isPinValid) {
            $this->addFlash('error', 'Incorrect 4-Digit PIN. Transaction halted.');
            return $this->redirectToRoute('app_checkout');
        }

        // --- Create the Order after Mock API Success ---
        $order = new Order();
        $order->setUser($user);
        $order->setOrderStatus('Paid via Mock API'); // Updated status
        $order->setTotalAmount(999.99); 
        // Capture the simulated transaction ID from the frontend
        $order->setTrackingNumber($request->request->get('transaction_id')); 
        
        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'API Verification Complete! Your order has been placed securely.');
        return $this->redirectToRoute('app_home');
    }
}