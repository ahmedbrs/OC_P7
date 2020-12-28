<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class UserController extends AbstractFOSRestController
{
    /**
     * @Rest\Get(
     *      path="/clients/{clientId}/users",
     *      name="list_users",
     *      requirements={"clientId"="\d+"}
     * )
     * @Rest\View(statusCode=200)
     */
    public function listAction(UserRepository $userRepository, $clientId)
    {
        $users = $userRepository->findBy(['client' => $clientId]);
        return $users;
    }

    /**
     * @Rest\Get(
     *      path="/clients/{clientId}/users/{id}",
     *      name="show_user",
     *      requirements={"clientId"="\d+", "id"="\d+"}
     * )
     * @Rest\View(statusCode=200)
     */
    public function showAction(UserRepository $userRepository, $clientId, $id)
    {
        $user = $userRepository->findOneBy(['client' => $clientId, 'id' => $id]);
        return $user;
    }

    /**
     * @Rest\Delete(
     *      path="/clients/{clientId}/users/{id}",
     *      name="delete_user",
     *      requirements={"clientId"="\d+", "id"="\d+"}
     * )
     * @Rest\View(statusCode=204)
     */
    public function deleteAction(UserRepository $userRepository, $clientId, $id)
    {
        $user = $userRepository->findOneBy(['client' => $clientId,'id' => $id]);
        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();
    }

    /**
     * @Rest\Post(
     *      path="/clients/{id}/users",
     *      name="create_user",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View(statusCode=201)
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function createAction(
        User $user, ClientRepository $clientRepository, $id,
        ConstraintViolationListInterface $validationErrors
    )
    {
        if (count($validationErrors) > 0) {
            return $this->view($validationErrors, 404);
        } else {
            $client = $clientRepository->findOneBy(['id' => $id]);
            
            if ($client) {
                $user->setClient($client);

                try {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();

                    return $user;
                } catch (\Exception $e) {
                    return $this->view('Erreur bdd', 400);
                }
            } else {
                return $this->view('client inconnu', 404);
            }
        }
    }
}