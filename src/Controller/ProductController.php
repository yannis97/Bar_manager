<?php
// src/Controller/BarController.php
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

      $listProducts = $repository->findAll();

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

    // Si la requête est en POST
        if ($request->isMethod('POST')) {
          
          $form->handleRequest($request);
          if ($form->isValid() and $form->get('save')->isClicked()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();
            $this->addFlash('product', 'Product successfully added !');
          }
          return $this->redirectToRoute('homepage');

        }

        // À ce stade, le formulaire n'est pas valide car :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau
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
      
      $listProducts = $repository->findAll();
      $length = count($listProducts);
      $em = $this->getDoctrine()->getManager();
      if($length>0)
      {
        $em->remove($listProducts[$length-1]);
        $em->flush();
        $this->addFlash('deleteProduct', 'Product deleted successfully !');
      }
      return $this->redirectToRoute('homepage');
    }
    public function sendProduct(string $product_name)
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
        $product = $repository->findOneByName($product_name);
        $product_price = $product->getPrix();
        $price = $price + $product_price;

        $currentOrder->setPrix($price);

        if(array_key_exists($product_name, $products))
        {
          $products[$product_name] += 1;
          
        }
        else{
          $products[$product_name] = 1;
        }
        $currentOrder->setProducts($products);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->redirectToRoute('homepage');
    }
}