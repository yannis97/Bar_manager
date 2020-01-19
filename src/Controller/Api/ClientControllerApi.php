<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ClientControllerApi extends AbstractController
{
	/**
     * @Route("/addClientApi", name="addClientApi", methods={"POST"})
     */
    public function addClient(Request $request)
    {
    	$data = json_decode($request->getContent(), true);
        if ($data['firstname'] !== "None" && $data['name'] !== "None")
        {
          $client = new Client();
          $client->setPrenom($data['firstname']);
          $client->setNom($data['name']);
          $client->setSolde(0);
          $em = $this->getDoctrine()->getManager();
          $em->persist($client);
          $em->flush();

          $id = $client->getId();

          return new JsonResponse(['result' => true,'id'=> $id , 'info' => 'Client created']);
        }
        else
        {
          return new JsonResponse(['result' => false , 'info'=> 'An error occured']);
        }
    }

    /**
     * @Route("/deleteClientApi/{id}", name="deleteClientApi", methods={"DELETE"})
     */
    public function deleteClient(Request $request, int $id)
    {
   	  $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Client')
      ;
      $client = $repository->findOneById($id);
      $em = $this->getDoctrine()->getManager();
      if ($client !== null)
      {
        $em->remove($client);
        $em->flush();
        return new JsonResponse(['result' => true , 'id' => $id, 'info' => 'client deleted']);
      }
      else
      {
        return new JsonResponse(['result' => false , 'info'=>'client not found']);
      }
    }

     /**
     * @Route("/clientsApi", name="getClientApi", methods={"GET"})
     */
    public function getClients()
    {
      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Client')
      ;
      $listClients = $repository->findAll();

      $encoders = array(new JsonEncode());
      $normalizers = array(new DateTimeNormalizer(),new ObjectNormalizer());
      $serializer = new Serializer($normalizers,$encoders);

      $jsonClients = $serializer->serialize($listClients, 'json');
      $clients = new JsonResponse();
      $clients->setContent($jsonClients);

      return $clients;

    }

    /**
     * @Route("/changeBalanceApi", name="changeBalanceApi", methods={"POST"})
     */
    public function changeBalance(Request $request)
    {
      $repository = $this->getDoctrine()
        ->getManager()
        ->getRepository('App\Entity\Client')
      ;
      $data = json_decode($request->getContent(), true);
      $client = $repository->findOneById($data['client_id']);

      if ($client !== null)
      {
        if ($data['deposit']!== 0)
        {
          $current_balance = $client->getSolde();
          $new_balance = $current_balance + $data['deposit'];
          $client->setSolde($new_balance);

          $em = $this->getDoctrine()->getManager();
          $em->flush();

          return new JsonResponse(['result' => true , 'balance'=>$new_balance]);
        }

          return new JsonResponse(['result' => false]);

      }
      else
      {
        return new JsonResponse(['result' => false]);
      }
    }
}
