<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Representation\Entities;
use App\Repository\CustomerRepository;
use App\Repository\CompanyRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api", name="api_")
 */
class CustomerController extends AbstractFOSRestController
{
    /**
     * Get a list of all customers from a company
     * @Rest\Get("/companies/{companyId}/customers", name="list_customers", requirements={"companyId"="\d+"})
     * @Rest\QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      default="1"
     * )
     * @Rest\QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      default="10"
     * )
     * @Rest\QueryParam(
     *      name="order",
     *      requirements="asc|desc",
     *      default="asc",
     *      description="Sort order (asc or desc)"
     * )
     * @OA\Tag(name="Customer")
     * @Security(name="Bearer")
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page's number.",
     *     @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Limit of items per page.",
     *     @OA\Schema(type="int")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Return a detailed customers list",
     *     @Model(type=Customer::class)
     * )
     * @OA\Response(
     *     response=400,
     *     description="Non existent company"
     * )
     * @OA\Response(
     *     response=401,
     *     description="JWT Token not found | Invalid JWT Token | Expired JWT Token"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Route not found or invalid id"
     * )
     * @Rest\View(statusCode=200)
     * @Cache(maxage="3600", public=true)
     */
    public function listAction(ParamFetcherInterface $paramFetcher, CustomerRepository $customerRepository, $companyId)
    {
        $pager = $customerRepository->search(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('order'),
            $companyId
        );
        if (count($pager) > 0) {
            return new Entities($pager);
        }
        return new JsonResponse('Non existent company', 400);
    }

    /**
     * Get details about a customer from a company
     * @Rest\Get(
     *      path="/companies/{companyId}/customers/{id}",
     *      name="show_customer",
     *      requirements={"companyId"="\d+", "id"="\d+"}
     * )
     * @OA\Tag(name="Customer")
     * @Security(name="Bearer")
     * @OA\Response(
     *     response=200,
     *     description="Return customer's details",
     *     @Model(type=Customer::class)
     * )
     * @OA\Response(
     *     response=400,
     *     description="Non existent company or customer"
     * )
     * @OA\Response(
     *     response=401,
     *     description="JWT Token not found | Invalid JWT Token | Expired JWT Token"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Route not found or invalid id"
     * )
     * @Rest\View(statusCode=200)
     * @Cache(maxage="3600", public=true)
     */
    public function showAction(CustomerRepository $customerRepository, $companyId, $id)
    {
        $customer = $customerRepository->findOneBy(['company' => $companyId, 'id' => $id]);
        if (!$customer) {
            return new JsonResponse('Non existent company or customer', 400);
        }
        return $customer;
    }

    /**
     * Delete a customer from a company
     * @Rest\Delete(
     *      path="/companies/{companyId}/customers/{id}",
     *      name="delete_customer",
     *      requirements={"companyId"="\d+", "id"="\d+"}
     * )
     * @OA\Tag(name="Customer")
     * @Security(name="Bearer")
     * @OA\Response(
     *     response=204,
     *     description=""
     * )
     * @OA\Response(
     *     response=400,
     *     description="Non existant company or customer"
     * )
     * @OA\Response(
     *     response=401,
     *     description="JWT Token not found | Invalid JWT Token | Expired JWT Token"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Route not found or invalid id"
     * )
     * @Rest\View(statusCode=204)
     */
    public function deleteAction(CustomerRepository $customerRepository, $companyId, $id)
    {
        $customer = $customerRepository->findOneBy(['company' => $companyId, 'id' => $id]);
        if (!$customer) {
            return new JsonResponse('Invalid company or customer id', 400);
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($customer);
        $em->flush();
    }

    /**
     * Create a customer for a company
     * @Rest\Post(
     *      path="/companies/{id}/customers",
     *      name="create_customer",
     *      requirements={"id"="\d+"}
     * )
     * @OA\Tag(name="Customer")
     * @Security(name="Bearer")
     * @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          @OA\Property(type="string", property="firstname"),
     *          @OA\Property(type="string", property="lastname"),
     *          @OA\Property(type="string", property="email"),
     *          required={"firstname", "lastname", "email"}
     *      )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Return customer's details created",
     *     @Model(type=Customer::class)
     * )
     * @OA\Response(
     *     response=400,
     *     description="{""property_path"": ""field name"", ""message"": ""error message""} | Non existant company"     
     * )
     * @OA\Response(
     *     response=401,
     *     description="JWT Token not found | Invalid JWT Token | Expired JWT Token"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Route not found or invalid id"
     * )
     * @OA\Response(
     *     response=500,
     *     description="Server error during the creation"
     * )
     * @Rest\View(statusCode=201)
     * @ParamConverter("customer", converter="fos_rest.request_body")
     */
    public function createAction(
        Customer $customer, CompanyRepository $companyRepository, $id,
        ConstraintViolationListInterface $validationErrors
    )
    {
        if (count($validationErrors) > 0) {
            return $this->view($validationErrors, 400);
        } else {
            $company = $companyRepository->findOneBy(['id' => $id]);
            
            if ($company) {
                $customer->setCompany($company);

                try {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($customer);
                    $em->flush();

                    return $customer;
                } catch (\Exception $e) {
                    return $this->view('Server error during the creation', 500);
                }
            } else {
                return $this->view('Non existant company', 400);
            }
        }
    }
}
