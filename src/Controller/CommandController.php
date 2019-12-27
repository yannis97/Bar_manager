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
    public function sendProduct(String $product_name)
    {
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $currentOrder = $repository->findOneBy(array('id'=>'DESC'));
        $products = $currentOrder->getProducts();
        $products[$product_name] += 1;
        $currentOrder->setProducts($products);

        var_dump($currentOrder->getProducts());
        $em = $this->getDoctrine()->getManager();
        $em->persist($currentOrder);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }
    public function displayCurrentCommand()
    {
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $currentOrder = $repository->findOneBy(array(), array('id' => 'DESC'));
        $listProducts = $currentOrder->getProducts();
        $price = $currentOrder->getPrix();

        return $this->render('order\order.html.twig', 
        array('listProducts' => $listProducts , 'price' => $price)
        );
    }
    public function addCommand()
    {

        $order = new Command();
        $order->setPrix(0);
        $order->setClientId(1);
        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();
        return $this->redirectToRoute('homepage');
    }
    public function displayAllOrders(){
        $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Command')
        ;
        $listOrders = $repository->findAll();
        $newOrderList = [];
        $totalSold = 0;
        foreach ($listOrders as $Order)
        {
           $Order_data = [];
           $Order_data['client'] =  $Order->getClientId();
           $Order_data['id'] = $Order->getId();
           $Order_data['listproducts'] = $Order->getProducts();
           $Order_data['price'] =  $Order->getPrix();
            array_push($newOrderList, $Order_data);
            $totalSold += $Order->getPrix();
        }
        //For eache orders, find client_id , find price, find listProducts with client id , find client NAME
        // Order_ID , Orders.client , Orders.price , Orders.Listproducts
        return $this->render('order\displayAll.html.twig', 
        array('listOrders' => $newOrderList , 'total' => $totalSold)
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
                    $currentOrder->setClientId(999);
                    $em->flush();
                    $this->addFlash('info', 'Command cleared !');
                    return $this->redirectToRoute('homepage');
                }
            }
        }

        // À ce stade, le formulaire n'est pas valide car :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau
        return $this->render('command\save.html.twig', array(
            'form' => $form->createView(),
        ));        
    }
}
