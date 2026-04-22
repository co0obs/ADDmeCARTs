<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $totalAmount = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $orderStatus = 'Processing';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $trackingNumber = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getTotalAmount(): ?float { return $this->totalAmount; }
    public function setTotalAmount(float $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }
    public function getOrderStatus(): ?string { return $this->orderStatus; }
    public function setOrderStatus(string $orderStatus): self { $this->orderStatus = $orderStatus; return $this; }
    public function getTrackingNumber(): ?string { return $this->trackingNumber; }
    public function setTrackingNumber(?string $trackingNumber): self { $this->trackingNumber = $trackingNumber; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}