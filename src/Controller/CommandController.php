<?php

namespace App\Controller;
use App\Entity\Product;
use App\Entity\Command;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommandController extends AbstractController
{
    public function displayCurrentCommand()
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
        return $this->render('order\order.html.twig', 
        array('listProducts' => $listProductsName , 'price' => $price)
        );
    }
    public function addCommand()
    {

        $new_order = new Command();
        $new_order->setPrix(0);
        $new_order->setClientId(0);
        $em = $this->getDoctrine()->getManager();
        $em->persist($new_order);
        $em->flush();
        return $this->redirectToRoute('homepage');
    }
    public function displayAllOrders(){
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
        return $this->render('order\displayAll.html.twig', 
        array('listOrders' => $newlistOrders , 'total' => $totalSold , 'listtotalProducts' => $listTotalProducts)
        );
    }

    public function saveCommand(Request $request)
    {
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $currentOrder = $repository->findOneBy(array(), array('id' => 'DESC'));

        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Client')
      ;

        $listClients = $repository->findAll();
        $listClientsName = []; 

        foreach ($listClients as $c)
        {
            array_push($listClientsName, $c->getPrenom()." ".$c->getNom());
        }

        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class);
        $formBuilder
            ->add('client',   ChoiceType::class , ['choices'=>[array_combine($listClientsName,$listClients)]])
            ->add('card',   SubmitType::class ,['label'=>'Pay Card'])
            ->add('cash',   SubmitType::class,['label'=>'Pay Cash'])
            ->add('clear',   SubmitType::class , ['label'=>'Clear'])
            ;
        $form = $formBuilder->getForm();

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()){
                $em = $this->getDoctrine()->getManager();
                $client= $form->get('client')->getData();
                $client_id = $client->getId();
                $currentOrder->setClientId($client_id);
                $current_solde = $client->getSolde();
                if($form->get('card')->isClicked())
                {
                    $new_solde = ($current_solde - $currentOrder->getPrix());
                    if($new_solde>=0)
                    {
                        $client->setSolde($new_solde);
                        $em->flush();
                        $this->addFlash('info', 'Command registered and paid by account !');
                        return $this->redirectToRoute('addCommand');
                    }
                    else{
                        $em->flush();
                        $this->addFlash('info', 'Command denied ! Not enough cash on account !');
                        return $this->redirectToRoute('homepage');
                    }
                }
                else if ($form->get('cash')->isClicked()) 
                {
                    $em->flush();
                    $this->addFlash('info', 'Command registered and paid by cash ! ');
                    return $this->redirectToRoute('addCommand');
                }
                else {
                    $currentOrder->setProducts([]);
                    $currentOrder->setPrix(0);
                    $currentOrder->setClientId(0);
                    $em->flush();
                    $this->addFlash('info', 'Command cleared !');
                    return $this->redirectToRoute('homepage');
                }
            }
        }
        return $this->render('order\save.html.twig', array(
            'form' => $form->createView(),
        ));
    }
    public function deleteAllCommand()
    {
      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Command')
      ;
      
      $listOrders = $repository->findAll();
      $length = count($listOrders);
      $em = $this->getDoctrine()->getManager();
      for($i=0;$i<$length;$i++)
      {
        $em->remove($listOrders[$i]);
        $em->flush();
        $this->addFlash('deleteProduct', 'All Orders deleted successfully !');
      }
      return $this->redirectToRoute('addCommand');
    }
}
