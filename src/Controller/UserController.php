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
use App\Form\UserType;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
* @Rest\RouteResource(
*     "User",
*     pluralize=false
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
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    
    public function __construct(
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository, 
        UserPasswordEncoderInterface $passwordEncoder
    )
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
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

            return $this->view([ 'status' => 'ok', 'user' => $user], Response::HTTP_CREATED);
        }

        return $this->view($form);
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
        die(var_dump());
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
