<?php

namespace App\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notification_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $notifications = $user->getNotifications();

        // Sort notifications by createdAt descending (newest first)
        $iterator = $notifications->getIterator();
        $iterator->uasort(function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        $sortedNotifications = new \Doctrine\Common\Collections\ArrayCollection(iterator_to_array($iterator));

        return $this->render('notification/index.html.twig', [
            'notifications' => $sortedNotifications,
        ]);
    }

    #[Route('/notifications/read-all', name: 'app_notification_read_all', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $notifications = $user->getUnreadNotifications();

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'All notifications marked as read.');
        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_read_single', methods: ['POST'])]
    public function markSingleAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot modify this notification.');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_notification_index');
    }
}
