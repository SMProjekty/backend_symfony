<?php

namespace App\Controller;
use App\Entity\Offer;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OfferController extends AbstractController
{

    #[Route('/offer', name: 'all_offers', methods: 'GET')]
    public function getOffers(ManagerRegistry $doctrine, SerializerInterface $serializer): Response
    {
        $offers = $doctrine->getRepository(Offer::class)->findBy(array('active' => true));
        $listOffers = $serializer->serialize($offers, 'json');
        if ($offers == NULL)
            return new Response('No Offers');
        return new Response($listOffers);
    }

    #[Route('/offer/add', name: 'add_offers', methods: 'POST')]
    public function addOffer(ManagerRegistry $doctrine, Request $request): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);

        if ($data_post['name'] == NULL || $data_post['price'] == NULL || $data_post['time'] == NULL) {
            return $this->json('Fields: name, price and time cant be null.', Response::HTTP_OK);
        }
            $newOffer = new Offer();
            $newOffer->setName($data_post['name']);
            $newOffer->setPrice($data_post['price']);
            $newOffer->setTime($data_post['time']);
            $newOffer->setActive(true);

            $entityManager->persist($newOffer);
            $entityManager->flush();

            return $this->json('Successfully added offer.', Response::HTTP_OK);
    }

    #[Route('/offer/update/{id}', name: 'update_offer', methods: 'PUT')]
    public function updateOffer(ManagerRegistry $doctrine, Request $request, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);
        $offer = $doctrine->getRepository(Offer::class)->findOneBy(array('id' => $id));

        if ($data_post['name'] == NULL || $data_post['price'] == NULL || $data_post['time'] == NULL) {
            return $this->json('Fields: name, price and time cant be null.', Response::HTTP_OK);
        }

        if ($data_post['price'] != $offer->getPrice() || $data_post['time'] != $offer->getTime()) {
            $offer->setActive(false);
            $newOffer = new Offer();
            $newOffer->setName($data_post['name']);
            $newOffer->setPrice($data_post['price']);
            $newOffer->setTime($data_post['time']);
            $newOffer->setActive(true);
            $entityManager->persist($newOffer);
        } else {
            $offer->setName($data_post['name']);
        }
        $entityManager->flush();


        return $this->json('Successfully updated offer.', Response::HTTP_OK);
    }

    #[Route('/offer/delete/{id}', name: 'delete_offer', methods: 'DELETE')]
    public function deleteOffer(ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $offer = $doctrine->getRepository(Offer::class)->findOneBy(array('id' => $id));
        $offer->setActive(false);
        $entityManager->flush();

        return $this->json('Successfully deleted offer.', Response::HTTP_OK);
    }
}