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
use App\Form\CartType;
use App\Entity\Cart;
use App\Repository\CartRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use App\Repository\TypeRepository;
use App\Form\CartProductsType;

/**
* @Rest\RouteResource(
*   "Cart",
*   pluralize=false,
* )
*/
class CartController extends FOSRestController  implements ClassResourceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CartRepository
     */
    private $cartRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    public function __construct(
        EntityManagerInterface $entityManager, 
        CartRepository $cartRepository,
        TypeRepository $typeRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @param $id
     *
     * @return Cart|null
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findCartById($id)
    {
        $cart = $this->cartRepository->find($id);

        if (!$cart) {
            throw new NotFoundHttpException();
        }

        return $cart;
    }

    public function getAction(String $id)
    {
        $cart = $this->findCartById($id);
        return $this->view($cart);
    }

    public function cgetAction()
    {
        $carts = $this->cartRepository->findAll();
        return $this->view($carts);
    }
    
    public function postAction(Request $request)
    {
        $cart = new Cart();
        $form = $this->createForm(CartType::class, $cart);
        $data = $request->request->all();
        
        if (isset($data['types'])) {
            $type = $this->typeRepository->findOneById($data['types']);
            $cart->addType($type);
        }

        $form->submit($data);

        if ($form->isValid()) {
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            return $this->view([ 'status' => 'ok', 'cart' => $cart], Response::HTTP_CREATED);
        }

        return $this->view($form);
    }

    public function putAction(Request $request, string $id)
    {
        $cart = $this->findCartById($id);

        $form = $this->createForm(CartType::class, $cart);

        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $this->view($form);
        }

        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function patchAction(Request $request, string $id)
    {
        $cart = $this->findCartById($id);

        $form = $this->createForm(CartType::class, $cart);

        $form->submit($request->request->all(), false);

        if (!$form->isValid()) {
            return $this->view($form);
        }
        
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function deleteAction(string $id)
    {
        $cart = $this->findCartById($id);
        
        $this->entityManager->remove($cart);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }
}
