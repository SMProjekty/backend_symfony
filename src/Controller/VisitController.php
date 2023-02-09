<?php

namespace App\Controller;

use App\Entity\AvailableTime;
use App\Entity\Customer;
use App\Entity\Worker;
use App\Entity\Offer;
use App\Entity\Visit;

use App\Controller\CustomerController;
// use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use Date;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\ManagerRegistry as DoctrineManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisitController extends AbstractController
{
    #[Route('/visits', name: 'getVisits', methods: 'POST')]
    public function getVisit(Request $request , ManagerRegistry $doctrine): Response
    {
        $date = json_decode($request->getContent(), true);
        $visits = $doctrine->getRepository(Visit::class)->findBy(array('date' => new DateTime($date['Date']), 'status' => true, 'canceled' => false), array('time' => 'asc'));

        return $this->json($visits);
    }

    #[Route('/visit/add', name: 'addVisit', methods: 'POST')]
    public function addVisit(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);

        if ($data_post['id_worker'] == NULL || $data_post['id_offer'] == NULL || $data_post['phone']== NULL) {
            return $this->json("Variable empty", Response::HTTP_OK);
        }

        //sprawdzenie czy nie ma już wizyty na dany dzień i godzinę u danego pracownika
        $visit = $doctrine->getRepository(visit::class)->findOneBy(array('worker'=>$data_post['id_worker'], 'date'=> (new DateTime($data_post['date'])), 'status'=>true, 'canceled' => false, 'time'=>(new DateTime($data_post['time']))));
        if($visit!=NULL){
            return $this->json("Critical error", Response::HTTP_OK);
        }

        $customer = $doctrine->getRepository(Customer::class)->findOneBy(array('phone' => $data_post['phone']));
        $worker = $doctrine->getRepository(Worker::class)->find($data_post['id_worker']);
        $offer = $doctrine->getRepository(Offer::class)->find($data_post['id_offer']);

        //aktualizacja danych klienta (przypisanych do numeru)
        if ($customer != NULL && $customer->getPhone() == $data_post['phone'])
            {
                if($customer->getName() != $data_post['name'] || ($customer->getSurname() != $data_post['surname'])){
                    $customer_controller = new CustomerController;
                    $customer_controller->updateCustomer($data_post['name'], $data_post['surname'], $data_post['phone'], $customer, $doctrine);
                }
            }

        //dodanie danych klienta
        if ($customer == NULL)
        {
            $customer_controller = new CustomerController;
            $customer_controller->addCustomer($data_post['name'], $data_post['surname'], $data_post['phone'], $doctrine);
        }

        $customer = $doctrine->getRepository(Customer::class)->findOneBy(array('phone' => $data_post['phone']));

        //dodanie wizyty
        if($customer!=NULL && $worker!=NULL && $offer!=NULL && $data_post['date']!=NULL && $data_post['time']!=NULL) {
            $newVisit = new Visit();
            $newVisit->setCustomer($customer);
            $newVisit->setWorker($worker);
            $newVisit->setOffer($offer);
            $newVisit->setDate(new DateTime($data_post['date']));
            $newVisit->setTime(new DateTime($data_post['time']));
            $newVisit->setStatus(true);
            $newVisit->setCanceled(false);

            $entityManager->persist($newVisit);
            $entityManager->flush();

            return $this->json('Added visit');
        }

        return $this->json("Critical error", Response::HTTP_OK);
    }

    #[Route('/visit/available', name: 'vsavailable', methods: 'POST')]
    public function getAvaiableVisits(Request $request , ManagerRegistry $doctrine): Response
    {
        //wysyłamy date, usługe i pracownika z frontu
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);

        if ($data_post['date'] == NULL || $data_post['offer_time'] == NULL || $data_post['worker_id']== NULL) {
            return new Response('Variable empty');
        }

        $date = $data_post['date'];
        $offer_time_length = $data_post['offer_time'];
        $worker_id = $data_post['worker_id'];

        //sprawdzenie czy dany pracownik ma urlop w danym dniu
        $date_to_check = new DateTime($data_post['date']);
        $query = $entityManager->createQuery('SELECT COUNT(a.id) as idVacation FROM App\Entity\Vacation a  WHERE a.worker = ?1 AND ?2 BETWEEN a.datefrom AND a.dateto');
        $query->setParameter(1, $worker_id);
        $query->setParameter(2, $date_to_check);

        $result = $query->getResult();

        if($result['0']['idVacation'] != 0){
            return $this->json('Employee on leave', Response::HTTP_OK);
        }

        //sprawdzenie dostępności godzin
        $visits = $doctrine->getRepository(Visit::class)->findBy(array('date' => new DateTime($date), 'worker'=>$worker_id), array('time' => 'asc'));
        $available_time_all = $doctrine->getRepository(AvailableTime::class)->findAll();

        //ustawianie true za każdym razem dla każdej godziny
        foreach($available_time_all as $hour) {
            $hour->setIsAvailable(true);
        }
        $entityManager->flush();

        //Pobieramy godzinę rozpoczęcia, obliczmy czas zakończenia
        foreach($visits as $visit) {
            $start_time = $visit->getTime();
            $end_time = new DateTime($start_time);
            $offer_time = $visit->getOffer()->getTime();
            $end_time->modify("+{$offer_time} minutes");
            //blokowanie czasu
            foreach($available_time_all as $hour)
                if($hour->getHour() == $start_time && $hour->isIsAvailable() && $hour->getHour() < $end_time)
                    $hour->setIsAvailable(false);
        }
        $entityManager->flush();
        $available_time = $doctrine->getRepository(AvailableTime::class)->findBy(array('isAvailable' => true));
        $disabled_time = $doctrine->getRepository(AvailableTime::class)->findBy(array('isAvailable' => false));

        //sprawdzanie czy dana usługa jest możliwa do wykonania w danym przedziale czasowym
        foreach($available_time as $hour1) {
            $time_hour = new DateTime($hour1->getHour());
            $end_offer_time = $time_hour->modify("+{$offer_time_length} minutes");
            foreach($disabled_time as $hour2) {
                $disabled_time_hour = new DateTime($hour2->getHour());
                if(($end_offer_time > $disabled_time_hour && $end_offer_time < $disabled_time_hour->modify("+{$offer_time_length} minutes")) || $end_offer_time > new DateTime('16:00:00')) {
                    $hour1->setIsAvailable(false);
                }
            }
        }
        $entityManager->flush();
        $available_time_final = $doctrine->getRepository(AvailableTime::class)->findBy(array('isAvailable' => true));
        $available_hours_to_send = array();
        foreach($available_time_final as $hour) {
            array_push($available_hours_to_send, $hour->getHour());
        }

        return $this->json($available_hours_to_send, Response::HTTP_OK);
    }


    #[Route('/visit/submit', name: 'submit_visit', methods: 'POST')]
    public function submitVisit(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);

        if ($data_post['id'] == NULL) {
            return $this->json('Variable empty');
        }

        $visit_id = $data_post['id'];
        $visit = $doctrine->getRepository(Visit::class)->findOneBy(array('id' => $visit_id));

        if ($data_post['id'] == NULL) {
            return $this->json('Critical error');
        }

        $visit->setStatus(false);
        $entityManager->flush();

        return $this->json('Submitted visit.');
    }

    #[Route('/visit/restore/{visit_id}', name: 'restore_visit', methods: 'GET')]
    public function restoreVisit(Request $request, ManagerRegistry $doctrine, int $visit_id): Response
    {
        $entityManager = $doctrine->getManager();

        $visit = $doctrine->getRepository(Visit::class)->findOneBy(array('id' => $visit_id));
        if ($visit == NULL) {
            return new Response('Critical error');
        }

        $visit->setStatus(true);
        $entityManager->flush();

        return $this->json('Restored visit.', Response::HTTP_OK);
    }

    #[Route('/visit/del/{id}', name: 'delete_visit', methods: 'DELETE')]
    public function deleteVisit(Request $request, ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $visit = $doctrine->getRepository(Visit::class)->findOneBy(array('id' => $id));

        if ($visit == NULL) {
            return $this->json('Critical error', Response::HTTP_OK);
        }

        $visit->setStatus(false);
        $visit->setCanceled(true);
        $entityManager->flush();

        return $this->json('Deleted visit.', Response::HTTP_OK);
    }
}
