<?php

namespace App\Controller;

use App\Entity\AvailableTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class AvailableTimeController extends AbstractController
{
    #[Route('/availabletime', name: 'listOfAvailableTime', methods: 'GET')]
    public function getAvailableTime(ManagerRegistry $doctrine, SerializerInterface $serializer): Response
    {
        $available_time = $doctrine->getRepository(AvailableTime::class)->findAll();
        $available_time = $serializer->serialize($available_time, 'json');
        if ($available_time == NULL)
            return new Response('No available time');
        return new Response($available_time);
    }
}
