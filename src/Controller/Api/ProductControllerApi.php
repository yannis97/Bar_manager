<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Product;
use App\Entity\Command;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ProductControllerApi extends AbstractController

{   
    /**
     * @Route("/productsApi", name="getProductApi", methods={"GET"})
     */
    public function getProducts()
    {
      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Product')
      ;
      $listProducts = $repository->findBy(array(), array('name' => 'ASC'));

      $encoders = array(new JsonEncode());
      $normalizers = array(new DateTimeNormalizer(),new ObjectNormalizer());
      $serializer = new Serializer($normalizers,$encoders);

      $jsonProducts = $serializer->serialize($listProducts, 'json');
      $products = new JsonResponse();
      $products->setContent($jsonProducts);

      return $products;

    }

    /**
     * @Route("/addProductApi", name="addProductApi", methods={"POST"})
     */
    public function addProduct(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data['name'] != "None" && $data['prix']>0)
        {
          $product = new Product();
          $product->setName($data['name']);
          $product->setPrix($data['prix']);

          $em = $this->getDoctrine()->getManager();
          $em->persist($product);
          $em->flush();

          $id = $product->getId();

          return new JsonResponse(['result' => true,'id'=> $id ,'name' => $data['name'],'prix'=> $data['prix']]);
        }
        else
        {
          return new JsonResponse(['result' => false]);
        }
    }


    /**
     * @Route("/deleteProductApi/{id}", name="deleteProductApi", methods={"DELETE"})
     */
    public function deleteProduct(Request $request,int $id)
    {
      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Product')
      ;
      $product = $repository->findOneById($id);
      $em = $this->getDoctrine()->getManager();
      if ($product !== null)
      {
        $product_name = $product->getName();
        $em->remove($product);
        $em->flush();

        return new JsonResponse(['result' => true ,'name' => $product_name]);
      }
      else
      {
        return new JsonResponse(['result' => false]);
      }

    }

    /**
     * @Route("/sendProductApi/{id}", name="sendProductApi", methods={"PUT"})
     */
    public function sendProduct(int $id)
    {
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $currentOrder = $repository->findOneBy(array(), array('id' => 'DESC'));
        $products = $currentOrder->getProducts();
        $price = $currentOrder->getPrix();

        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Product')
        ;

        $product = $repository->findOneById($id);

        //Check if the product exist
        if ($product !== null)
        {
            $product_price = $product->getPrix();
            $product_name = $product->getName();
            $price = $price + $product_price;
            $currentOrder->setPrix($price);
            if(array_key_exists($id, $products))
            {
              $products[$id] += 1;        
            }
            else{
              $products[$id] = 1;
            }
            $currentOrder->setProducts($products);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return new JsonResponse(['result' => true]);

        }

        return new JsonResponse(['result' => false]);
    }
}