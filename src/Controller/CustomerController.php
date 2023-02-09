<?php

namespace App\Controller;

use App\Entity\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\AvailableTime;
use App\Entity\Worker;
use App\Entity\Offer;
use App\Entity\Visit;


use Date;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\ManagerRegistry as DoctrineManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CustomerController extends AbstractController
{
    #[Route('/customer/check', name: 'checkCustomer', methods: 'POST')]
    public function getCustomerByPhone(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);
        $customer = $doctrine->getRepository(Customer::class)->findOneBy(array('phone' => $data_post['phone']));

	    if($data_post['phone'] == NULL)
            return new Response('Phone number is null');

        if($customer == NULL)
            return $this->json("No phone number", Response::HTTP_OK);
        return $this->json($customer, Response::HTTP_OK);
    }

    public function updateCustomer($name, $surname, $phone , $customer, ManagerRegistry $doctrine){
        $entityManager = $doctrine->getManager();

        $customer->setName($name);
        $customer->setSurname($surname);
        $entityManager->flush();

        //return $this->json('Successfully updated customer.', Response::HTTP_OK);
    }

    public function addCustomer($name, $surname, $phone, ManagerRegistry $doctrine){
        $entityManager = $doctrine->getManager();

        $newCustomer = new Customer();
        $newCustomer->setName($name);
        $newCustomer->setSurname($surname);
        $newCustomer->setphone($phone);

        $entityManager->persist($newCustomer);
        $entityManager->flush();

        //return $this->json('Successfully added new customer.', Response::HTTP_OK);
    }
}


