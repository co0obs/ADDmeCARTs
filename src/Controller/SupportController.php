<?php

namespace App\Controller;

use App\Entity\SupportTicket;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[IsGranted('ROLE_CUSTOMER')]
class SupportController extends AbstractController
{
    #[Route('/support', name: 'app_support_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tickets = $entityManager->getRepository(SupportTicket::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('support/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/support/new', name: 'app_support_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $orders = $entityManager->getRepository(Order::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');
            $orderId = $request->request->get('order_id');
            $attachment = $request->files->get('attachment');

            if ($subject && $message) {
                $ticket = new SupportTicket();
                $ticket->setUser($user);
                $ticket->setSubject($subject);
                $ticket->setMessage($message);
                $ticket->setStatus('Open');

                if ($orderId) {
                    $order = $entityManager->getRepository(Order::class)->find($orderId);
                    if ($order && $order->getUser() === $user) {
                        $ticket->setRelatedOrder($order);
                    }
                }

                if ($attachment) {
                    $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                    
                    // Fallback to getClientOriginalExtension to avoid requiring the fileinfo PHP extension
                    $extension = $attachment->getClientOriginalExtension() ?: 'bin';
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                    try {
                        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/support';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $attachment->move($uploadDir, $newFilename);
                        $ticket->setAttachmentPath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Failed to upload attachment.');
                    }
                }
                
                $entityManager->persist($ticket);
                $entityManager->flush();

                $this->addFlash('success', 'Your support ticket has been submitted. We will get back to you soon!');
                return $this->redirectToRoute('app_support_index');
            }

            $this->addFlash('danger', 'Subject and Message are required.');
        }

        return $this->render('support/new.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/support/{id}', name: 'app_support_show')]
    public function show(SupportTicket $ticket): Response
    {
        if ($ticket->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot view this ticket.');
        }

        return $this->render('support/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }
}
