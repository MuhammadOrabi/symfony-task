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
use App\Entity\Type;
use App\Form\TypeType;
use App\Repository\TypeRepository;

/**
* @Rest\RouteResource(
*   "Type",
*   pluralize=false,
* )
*/
class TypeController extends FOSRestController  implements ClassResourceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
        TypeRepository $typeRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @param $id
     *
     * @return Type|null
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findTypeById($id)
    {
        $type = $this->typeRepository->find($id);

        if (!$type) {
            throw new NotFoundHttpException();
        }

        return $type;
    }

    public function getAction(String $id)
    {
        $type = $this->findTypeById($id);
        return $this->view($type);
    }

    public function cgetAction()
    {
        $types = $this->typeRepository->findAll();
        return $this->view($types);
    }
    
    public function postAction(Request $request)
    {
        $type = new Type();
        $form = $this->createForm(TypeType::class, $type);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->entityManager->persist($type);
            $this->entityManager->flush();

            return $this->view([ 'status' => 'ok', 'type' => $type], Response::HTTP_CREATED);
        }

        return $this->view($form);
    }

    public function putAction(Request $request, string $id)
    {
        $type = $this->findTypeById($id);

        $form = $this->createForm(TypeType::class, $type);

        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $this->view($form);
        }

        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function patchAction(Request $request, string $id)
    {
        $type = $this->findTypeById($id);

        $form = $this->createForm(TypeType::class, $type);

        $form->submit($request->request->all(), false);

        if (!$form->isValid()) {
            return $this->view($form);
        }
        
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    public function deleteAction(string $id)
    {
        $type = $this->findTypeById($id);
        
        $this->entityManager->remove($type);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }
}
