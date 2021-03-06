<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Representation\Entities;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api", name="api_")
 * @Cache(maxage="3600", public=true)
 */
class ProductController extends AbstractFOSRestController
{
    /**
     * Get list of all products
     * @Rest\Get("/products", name="list_products")
     * @Rest\QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      default="1",
     *      description="The page's number."
     * )
     * @Rest\QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      default="10",
     *      description="Limit of items per page."
     * )
     * @Rest\QueryParam(
     *      name="order",
     *      requirements="asc|desc",
     *      default="asc",
     *      description="Sort order (asc or desc)"
     * )
     * @OA\Tag(name="Products")
     * @Security(name="Bearer")
     * @OA\Response(
     *     response=200,
     *     description="Return the products' list of BileMo",
     *     @Model(type=Product::class)
     * )
     * @OA\Response(
     *     response=401,
     *     description="JWT Token not found | Invalid JWT Token | Expired JWT Token"
     * )
     * @Rest\View(statusCode=200)
     */
    public function listAction(ParamFetcherInterface $paramFetcher, ProductRepository $productRepository)
    {
        $pager = $productRepository->search(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('order')
        );
        return new Entities($pager);
    }

    /**
     * Get details about a product
     * @Rest\Get(
     *      path="/products/{id}",
     *      name="show_product",
     *      requirements={"id"="\d+"}
     * )
     * @OA\Tag(name="Products")
     * @Security(name="Bearer")
     * @OA\Response(
     *     response=200,
     *     description="Return product's details",
     *     @Model(type=Product::class)
     * )
     * @OA\Response(
     *     response=401,
     *     description="JWT Token not found | Invalid JWT Token | Expired JWT Token"
     * )
     * @OA\Response(
     *     response=400,
     *     description="Non existent product"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Route not found or invalid id"
     * )
     * @Rest\View(statusCode=200)
     */
    public function showAction(ProductRepository $productRepository, $id)
    {
        if ($product = $productRepository->findOneBy(['id' => $id])) {
            return $product;
        }
        return new JsonResponse('Non existent product', 400);
    }
}
