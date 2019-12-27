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
        $products[product_name] += 1;
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
        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();
        return $this->redirectToRoute('homepage');
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
            $em = $this->getDoctrine()->getManager();
            $form->handleRequest($request);
            if ($form->isValid()){

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
                        $this->addFlash('info', 'Command registered !','Balance : ', $new_solde);
                        return $this->redirectToRoute('addCommand');
                    }
                    else{
                        $this->addFlash('info', 'Command denied !', 'Balance : ',$current_solde);
                        return $this->redirectToRoute('homepage');
                    }
                }
                else if ($form->get('cash')->isClicked()) 
                {
                    $this->addFlash('info', 'Command registered !','Balance : ', $current_solde);
                    return $this->redirectToRoute('addCommand');
                }
                else {
                    $em->remove($currentOrder);
                    $this->addFlash('info', 'Command cleared !');
                    return $this->redirectToRoute('addCommand');
                }
            }
              $em->flush();
        }

        // À ce stade, le formulaire n'est pas valide car :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau
        return $this->render('command\save.html.twig', array(
            'form' => $form->createView(),
        ));        
    }
}
