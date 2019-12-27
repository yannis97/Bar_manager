<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Client;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvents;
class ClientController extends AbstractController
{
    /**
     * @Route("/client", name="client")
     */
    public function index()
    {
        return $this->render('client/index.html.twig', [
            'controller_name' => 'ClientController',
        ]);
    }
    public function addClient(Request $request)
    {
        $client = new Client();

        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $client);
        $formBuilder
          ->add('nom',   TextType::class ,['data'=>'None'])
          ->add('prenom',   TextType::class ,['data'=>'None'])
          ->add('save',   SubmitType::class)
          ->add('back',   SubmitType::class)
        ;
        $form = $formBuilder->getForm();

    // Si la requête est en POST
        if ($request->isMethod('POST')) {
          
          $form->handleRequest($request);
          if ($form->isValid() and $form->get('save')->isClicked()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($client);
            $em->flush();
            $this->addFlash('client', 'Client successfully added !');
          }
          return $this->redirectToRoute('homepage');

        }
        return $this->render('client\add.html.twig', array(
          'form' => $form->createView(),
        ));
    }
    public function changeBalance(Request $request)
    {
        $solde = 0;
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
            ->add('save',   SubmitType::class)
            ->add('back',   SubmitType::class)
            ->add('loadinfo',   SubmitType::class , ['label'=>'Load Balance'])
            ->add('delete',   SubmitType::class , ['label'=>'Delete'])
            ->add('new_balance',   NumberType::class , ['data'=>'0'])
            ;
        $form = $formBuilder->getForm();

        if ($request->isMethod('POST')) {
           
            $form->handleRequest($request);
            if ($form->get('loadinfo')->isClicked())
            {
                $client= $form->get('client')->getData();
                $solde = $client->getSolde();
                return $this->render('client\balance.html.twig', array(
                    'form' => $form->createView(), 'solde' => $solde
                  ));
            }
            if($form->isValid() and $form->get('save')->isClicked())
            {
                $client= $form->get('client')->getData();
                $new_solde = $form->get('new_balance')->getData();
                $current_solde = $client->getSolde();
                $client->setSolde($current_solde + $new_solde);
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                $this->addFlash('balance', 'Balance changed successfully !');
                return $this->render('client\balance.html.twig', array(
                    'form' => $form->createView(), 'solde' => $current_solde + $new_solde
                  ));
            }
            if($form->isValid() and $form->get('delete')->isClicked())
            {
                $client= $form->get('client')->getData();
                $em = $this->getDoctrine()->getManager();
                $em->remove($client);
                $em->flush();
                $this->addFlash('deleteClient', 'Client deleted successfully !');
                return $this->redirectToRoute('homepage');
            }
            else
            {
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('client\balance.html.twig', array(
            'form' => $form->createView(), 'solde' => $solde
          ));
    }
}
