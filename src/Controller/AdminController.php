<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    // 1. THE SECRET SETUP ROUTE (You can keep this here just in case!)
    #[Route('/admin/setup', name: 'app_admin_setup')]
    public function setupAdmin(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $entityManager->flush();

        return new Response('<div style="text-align: center; margin-top: 50px;"><h1>Success!</h1><a href="/admin">Go to Admin Dashboard</a></div>');
    }

    // 2. THE MAIN DASHBOARD
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Fetch ALL orders in the system (ASC so chart data is chronological)
        $orders = $entityManager->getRepository(Order::class)->findBy([], ['createdAt' => 'ASC']);

        $totalRevenue = 0;
        $totalOrders = 0;
        $salesData = [];

        foreach ($orders as $order) {
            $totalOrders++;
            
            // Only count non-cancelled orders for revenue
            if ($order->getOrderStatus() !== 'Cancelled') {
                $totalRevenue += $order->getTotalAmount();
                
                // Group by Date
                $dateStr = $order->getCreatedAt()->format('M d, Y');
                if (!isset($salesData[$dateStr])) {
                    $salesData[$dateStr] = 0;
                }
                $salesData[$dateStr] += $order->getTotalAmount();
            }
        }

        // Reverse for the table so newest orders appear at the top
        $tableOrders = array_reverse($orders);

        return $this->render('admin/index.html.twig', [
            'orders' => $tableOrders,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'chartDates' => json_encode(array_keys($salesData)),
            'chartAmounts' => json_encode(array_values($salesData)),
        ]);
    }

    // 3. THE STATUS UPDATE ROUTE (Handles the Save Button)
    #[Route('/admin/order/{id}/status', name: 'app_admin_update_status', methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $newStatus = $request->request->get('status');
        if ($newStatus) {
            $order->setOrderStatus($newStatus);
            $entityManager->flush(); // Save the new status to the database
            
            $ref = $order->getReferenceNumber() ?? $order->getId();
            $this->addFlash('success', "Order #$ref status successfully updated to: $newStatus");
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/admin/support', name: 'app_admin_support')]
    public function supportIndex(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tickets = $entityManager->getRepository(\App\Entity\SupportTicket::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/support.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/admin/support/{id}', name: 'app_admin_support_show', methods: ['GET', 'POST'])]
    public function supportShow(\App\Entity\SupportTicket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $adminReply = $request->request->get('adminReply');
            $status = $request->request->get('status');

            if ($adminReply !== null) {
                $ticket->setAdminReply($adminReply);
            }
            
            if ($status) {
                $ticket->setStatus($status);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Ticket #' . $ticket->getId() . ' updated successfully!');

            return $this->redirectToRoute('app_admin_support');
        }

        return $this->render('admin/support_show.html.twig', [
            'ticket' => $ticket,
        ]);
    }
}