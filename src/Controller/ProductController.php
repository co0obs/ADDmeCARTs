<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
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
    public function index(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
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
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK
        if ($product->getSeller() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit a product you do not own.');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
}