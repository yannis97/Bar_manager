<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Product;
use App\Entity\Command;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController

{   

    public function displayProduct()
    {
      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Product')
      ;
      $listProducts = $repository->findBy(array(), array('name' => 'ASC'));
      return $this->render('product\products.html.twig', 
        array('listProducts' => $listProducts)
      );
    }
    public function addProduct(Request $request)
    {
        $product = new Product();

        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $product);
        $formBuilder
          ->add('name',   TextType::class ,['data'=>'None'])
          ->add('prix',   NumberType::class , ['data'=>'0'])
          ->add('save',   SubmitType::class)
          ->add('back',   SubmitType::class)
        ;
        $form = $formBuilder->getForm();

        if ($request->isMethod('POST')) {          
          $form->handleRequest($request);
          if ($form->isValid() and $form->get('save')->isClicked()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();
            $this->addFlash('product', 'Product successfully added !');
          }
          if (!$form->isValid())
          {$this->addFlash('error', 'Incorrect value for product price !');}
          return $this->redirectToRoute('homepage');
        }

        return $this->render('product\add.html.twig', array(
          'form' => $form->createView(),
        ));
    }
    public function deleteProduct()
    {
      $repository = $this->getDoctrine()
      ->getManager()
      ->getRepository('App\Entity\Product')
      ;
      $product = $repository->findOneBy(array(), array('id' => 'DESC'));
      $em = $this->getDoctrine()->getManager();
      if ($product !== null)
      {
        $em->remove($product);
        $this->addFlash('deleteProduct', 'Product '. $product->getName() .' deleted successfully !');
        $em->flush();
      }
      else
      {
        $this->addFlash('error', 'No product found !');
      }
      return $this->redirectToRoute('homepage');
    }
    public function sendProduct(int $product_id)
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

        $product = $repository->findOneById($product_id);
        //Check if product_id exist
        if ($product === null)
        {
          $this->addFlash('error', 'This product does not exist !');
          return $this->redirectToRoute('homepage');
        }
        $product_price = $product->getPrix();
        $product_name = $product->getName();
        $price = $price + $product_price;

        $currentOrder->setPrix($price);

        if(array_key_exists($product_id, $products))
        {
          $products[$product_id] += 1;        
        }
        else{
          $products[$product_id] = 1;
        }
        $currentOrder->setProducts($products);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->redirectToRoute('homepage');
    }
}