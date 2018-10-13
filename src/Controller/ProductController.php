<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProductType;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use App\Repository\CartRepository;
use App\Repository\TypeRepository;

/**
* @Rest\RouteResource(
*   "Product",
*   pluralize=false,
* )
*/
class ProductController extends FOSRestController  implements ClassResourceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;
    
    public function __construct(
        EntityManagerInterface $entityManager, 
        ProductRepository $productRepository,
        TypeRepository $typeRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @param $id
     *
     * @return Product|null
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findProductById($id)
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw new NotFoundHttpException();
        }

        return $product;
    }

    public function getAction(String $id)
    {
        $product = $this->findProductById($id);
        return $this->view($product);
    }

    public function cgetAction()
    {
        $products = $this->productRepository->findAll();
        return $this->view($products);
    }
    
    public function postAction(Request $request)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $data = $request->request->all();

        if (isset($data['types'])) {
            $type = $this->typeRepository->findOneById($data['types']);
            $product->addType($type);
        }

        $form->submit($data);

        if ($form->isValid()) {
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->view([ 'status' => 'ok', 'product' => $product], Response::HTTP_CREATED);
        }

        return $this->view($form);
    }

    public function putAction(Request $request, string $id)
    {
        $product = $this->findProductById($id);

        $form = $this->createForm(ProductType::class, $product);

        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $this->view($form);
        }

        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function patchAction(Request $request, string $id)
    {
        $product = $this->findProductById($id);

        $form = $this->createForm(ProductType::class, $product);

        $form->submit($request->request->all(), false);

        if (!$form->isValid()) {
            return $this->view($form);
        }
        
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function deleteAction(string $id)
    {
        $product = $this->findProductById($id);
        
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }
}
