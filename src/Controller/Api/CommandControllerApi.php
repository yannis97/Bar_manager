<?php

namespace App\Controller\Api;
use App\Entity\Product;
use App\Entity\Command;
use App\Entity\Client;

use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
class CommandControllerApi extends AbstractController
{
    /**
     * @Route("/currentCommandApi", name="currentCommandApi", methods={"GET"})
     */
    public function getCurrentCommand()
    {
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $currentOrder = $repository->findOneBy(array(), array('id' => 'DESC'));
        $listProducts = $currentOrder->getProducts();
        $price = $currentOrder->getPrix();
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Product')
        ;
        $listProductsName = [];
        if ($listProducts !== null)
        {
            foreach ($listProducts as $key => $value)
            {
                $product = $repository->findOneById($key);
                if($product!==null)
                {
                    $product_name = $product->getName();
                    $listProductsName[$product_name]=$value;
                }
            }
        }
        $currentOrderDetails = ['id'=> $currentOrder->getId() , 'products'=> $listProductsName , 'price' => $price];

        $encoders = array(new JsonEncode());
        $normalizers = array(new DateTimeNormalizer(),new ObjectNormalizer());
        $serializer = new Serializer($normalizers,$encoders);
        $jsonCurrentOrder = $serializer->serialize($currentOrderDetails, 'json');
        $response = new JsonResponse();
        $response->setContent($jsonCurrentOrder);
        return $response;
    }

    /**
     * @Route("/addCommandApi", name="addCommandApi", methods={"PUT"})
     */
    public function addCommand()
    {

        $new_order = new Command();
        $new_order->setPrix(0);
        $new_order->setClientId(0);
        $em = $this->getDoctrine()->getManager();
        $em->persist($new_order);
        $em->flush();
        return New JsonResponse(['result'=>true]);
    }

    /**
     * @Route("/allCommandApi", name="allCommandApi", methods={"GET"})
     */
    public function getAllOrders(){
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $listOrders = $repository->findAll();
        // newOrderList will contain all informations we want in details for all orders such as the name of products
        $newlistOrders = [];
        $totalSold = 0;
        $listTotalProducts = [];

        foreach ($listOrders as $Order)
        {
            $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository('App\Entity\Product')
            ;

           $listProducts = $Order->getProducts();
           $listProductsName = [];
           if ($listProducts !== null)
           {
               foreach ($listProducts as $key => $value)
               {
                   $product = $repository->findOneById($key);
                   if($product!==null)
                   {
                       $product_name = $product->getName();
                       $listProductsName[$product_name]=$value;
                       if(array_key_exists($product_name, $listTotalProducts))
                       {
                        $listTotalProducts[$product_name]+=$value;      
                       }
                       else{
                        $listTotalProducts[$product_name]=1;
                       }
                   }
               }
           }
           $repository = $this->getDoctrine()
           ->getManager()
           ->getRepository('App\Entity\Client')
           ;
           $client_id = $Order->getClientId();
           $client = $repository->findOneById($client_id);
           $clientFullName = "";
           if($client !== null)
           {
               $clientFullName = $client->getPrenom().' '.$client->getNom();
           }
           else{
                $clientFullName = "Unknown";
           }

           $Order_data = [];
           $Order_data['client'] =  $clientFullName;
           $Order_data['id'] = $Order->getId();
           $Order_data['listproducts'] = $listProductsName;
           $Order_data['price'] =  $Order->getPrix();
            array_push($newlistOrders, $Order_data);
            $totalSold += $Order->getPrix();
        }
        arsort($listTotalProducts);
        $jsonOrdersToSerialize = ['orders'=>$newlistOrders,'total_sold'=>$totalSold,'products_sold'=>$listTotalProducts];
        $encoders = array(new JsonEncode());
        $normalizers = array(new DateTimeNormalizer(),new ObjectNormalizer());
        $serializer = new Serializer($normalizers,$encoders);
        $jsonOrders = $serializer->serialize($jsonOrdersToSerialize, 'json');
        $response = new JsonResponse();
        $response->setContent($jsonOrders);
        return $response;
    }

    /**
     * @Route("/clearCommandApi", name="clearCommandApi", methods={"PUT"})
     */
    public function clearCommand(Request $request)
    {
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $currentOrder = $repository->findOneBy(array(), array('id' => 'DESC'));
        $currentOrder->setProducts([]);
        $currentOrder->setPrix(0);
        $currentOrder->setClientId(0);

        $em = $this->getDoctrine()->getManager();
        $em->flush();
        return New JsonResponse (['result'=>true]);
    }

    /**
     * @Route("/saveCommandApi", name="saveCommandApi", methods={"POST"})
     */
    public function saveCommand(Request $request)
    {
      $info ='';
      $result = false;
      $data = json_decode($request->getContent(), true);

      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Client')
      ;
      $client = $repository->findOneById($data['client_id']);
      if($client !== null)
      {
          $repository = $this->getDoctrine()
          ->getManager()
          ->getRepository('App\Entity\Command')
          ;
          $currentOrder = $repository->findOneBy(array(), array('id' => 'DESC'));

          if($data['card']===true)
          {
            $client_sold = $client->getSolde();
            $new_solde = $client_sold - $currentOrder->getPrix();
            if($new_solde>=0)
            {
              $info = 'Order paid by account';
              $result = true;
              $currentOrder->setClientId($client->getId());
              $client->setSolde($new_solde);
              $em = $this->getDoctrine()->getManager();
              $new_order = new Command();
              $new_order->setPrix(0);
              $new_order->setClientId(0);
              $em->persist($new_order);
              $em->flush();
            }
            else
            {

              $info = 'Not enough cash on account';
            }
          }
          else {

              $info = 'Order paid by cash';
              $currentOrder->setClientId($client->getId());
              $result = true;
              $em = $this->getDoctrine()->getManager();
              $new_order = new Command();
              $new_order->setPrix(0);
              $new_order->setClientId(0);
              $em->persist($new_order);
              $em->flush();
          }
      }
      else
      {
        $info = 'Client not found';
      }
      return New JsonResponse(['result'=>$result , 'info'=> $info]);

    }
}
