<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserType;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repository\TypeRepository;
use App\Entity\Cart;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr;
use App\Repository\ProductRepository;

/**
* @Rest\RouteResource(
*   "User",
*   pluralize=false,
* )
*/
class UserController extends FOSRestController  implements ClassResourceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

     /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    
    public function __construct(
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository, 
        TypeRepository $typeRepository, 
        ProductRepository $productRepository, 
        UserPasswordEncoderInterface $passwordEncoder
    )
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->typeRepository = $typeRepository;
        $this->productRepository = $productRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param $id
     *
     * @return User|null
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findUserById($id)
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        return $user;
    }

    public function initCartsByType(User $user, Array $typesCritiria)
    {
        foreach ($typesCritiria as $typeCritiria) {
            $type = $this->typeRepository->findOneOrCreate($typeCritiria);
            $cart = new Cart;
            $cart->addType($type);
            $cart->setUser($user);
            
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
    
            $user->addCart($cart);
        }
    }

    public function getAction(String $id)
    {
        $user = $this->findUserById($id);
        return $this->view($user);
    }

    public function cgetAction()
    {
        return $this->view(
            $this->userRepository->findAll()
        );
    }
    
    public function postAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->submit($request->request->all());

        if ($form->isValid()) {

            $password = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->initCartsByType(
                $user,
                [
                    ['slug' => 'wish-list', 'title' => 'Wish list'],
                    ['slug' => 'order-list', 'title' => 'Order list']
                ]
            );

            return $this->view([ 'status' => 'ok', 'user' => $user], Response::HTTP_CREATED);
        }

        return $this->view($form);
    }

    public function getProductsAction(Request $request, string $id, string $slug)
    {
        $user = $this->findUserById($id);
        $type = $this->typeRepository->findOneBy(['slug' => $slug]);
        $cart = $user->findCartByType($type);
        
        return $this->view($cart->getProducts(), Response::HTTP_OK);
    }

    public function postProductsAction(Request $request, string $id, string $slug)
    {
        $user = $this->findUserById($id);
        $type = $this->typeRepository->findOneBy(['slug' => $slug]);
        $cart = $user->findCartByType($type);
        
        $productsId = $request->get('products');
        $products = $this->productRepository->findBy(['id' => $productsId]);
        array_map(function($product) use ($cart) {
            $cart->addProduct($product);
        }, $products);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        return $this->view($cart, Response::HTTP_OK);
    }

    public function putProductsAction(Request $request, string $id, string $slug)
    {
        $user = $this->findUserById($id);
        $type = $this->typeRepository->findOneBy(['slug' => $slug]);
        $cart = $user->findCartByType($type);
        
        $productsId = $request->get('products');
        $products = $this->productRepository->findBy(['id' => $productsId]);
        array_map(function($product) use ($cart) {
            $cart->removeProduct($product);
        }, $products);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        return $this->view($cart, Response::HTTP_OK);
    }

    public function deleteProductsAction(Request $request, string $id, string $slug)
    {
        $user = $this->findUserById($id);
        $type = $this->typeRepository->findOneBy(['slug' => $slug]);
        $cart = $user->findCartByType($type);
        
        $products = $cart->getProducts()->map(function($product) use ($cart) {
            $product = $this->productRepository->findOneBy(['id' => $product->getId()]);
            $cart->removeProduct($product);
        });
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        return $this->view($cart, Response::HTTP_OK);
    }

    public function putAction(Request $request, string $id)
    {
        $user = $this->findUserById($id);

        $form = $this->createForm(UserType::class, $user);

        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $this->view($form);
        }

        if ($user->getPlainPassword()) {
            $password = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
        }

        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function patchAction(Request $request, string $id)
    {
        $user = $this->findUserById($id);

        $form = $this->createForm(UserType::class, $user);

        $form->submit($request->request->all(), false);

        if (!$form->isValid()) {
            return $this->view($form);
        }
        $password = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);
        
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function deleteAction(string $id)
    {
        $user = $this->findUserById($id);
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }
}
