<?php

namespace App\Controller;

use App\Entity\Worker;
use App\Entity\Offer;
use App\Entity\Visit;

use App\Controller\CustomerController;

use Date;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\ManagerRegistry as DoctrineManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


class RaportsController extends AbstractController
{

    #[Route('/raports', name: 'get_raposrts', methods: 'POST')]
    public function getRaport(Request $request , ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);

        $date_start = new DateTime($data_post['date_start']);
        $date_end = new DateTime($data_post['date_end']);
        $type_of_raport = $data_post['type_of_raport'];

        //Sprawdzenie danych
        if($data_post['date_start'] == NULL || $data_post['date_end'] == NULL || $data_post['type_of_raport'] == NULL){
            return new Response('Variable empty');
        }

        //Przychody - cały zakład (niewyszczególnieni pracownicy)
        if($type_of_raport == 1){
            $query = $entityManager->createQuery('SELECT a.date, COUNT(a.id) as visitsCount, SUM(b.time) AS timeSum, SUM(b.price) AS income  FROM App\Entity\Visit a INNER JOIN App\Entity\Offer b WHERE a.offer = b.id  AND a.date >= ?1 AND a.date <= ?2 AND a.status = false AND a.canceled = false group by a.date order by a.date asc ');
            $query->setParameter(1, $date_start);
            $query->setParameter(2, $date_end);

            $query2 = $entityManager->createQuery('SELECT COUNT(a.id) as allVisitsCount, SUM(b.time) AS allTimeSum, SUM(b.price) AS allIncome  FROM App\Entity\Visit a INNER JOIN App\Entity\Offer b WHERE a.offer = b.id  AND a.date >= ?1 AND a.date <= ?2 AND a.status = false AND a.canceled = false');
            $query2->setParameter(1, $date_start);
            $query2->setParameter(2, $date_end);

            $data = $query->getResult();
            $data2 = $query2->getResult();

            return $this->json(['details_data'=>$data, 'sum_data'=>$data2]);
        }

        //Przychody raport szczegółowy
        if($type_of_raport == 2){

            $second_data =array();

            $query = $entityManager->createQuery('SELECT a.date FROM App\Entity\Visit a  WHERE   a.date >= ?1 AND a.date <= ?2  group by a.date order by a.date asc ');
            $query->setParameter(1, $date_start);
            $query->setParameter(2, $date_end);
            $allDate = $query->getResult(); //Wszystkie daty z wizyt zadanego okresu

            $allWorkers = $doctrine->getRepository(Worker::class)->findBy(array('active' => true));

            //dla wszystkich dat(dni)
            foreach($allDate as $date){

                $first_data = array();

                //dla wszystkich aktywnych pracowników
                foreach($allWorkers as $worker){

                    $query = $entityManager->createQuery('SELECT c.name as Name, c.surname as Surname, COUNT(a.id) as visitsCount, SUM(b.time) AS timeSum, SUM(b.price) AS income  FROM App\Entity\Visit a , App\Entity\Offer b ,  App\Entity\Worker c WHERE a.offer = b.id  AND a.worker = c.id AND a.date = ?1  AND a.worker = ?2 AND a.status = false AND a.canceled = false group by a.date order by a.date asc ');

                    $query->setParameter(1, $date['date']);
                    $query->setParameter(2, $worker);

                    $details = $query->getResult(); //szczegóły z danego dnia dla pojedynczego pracownika

                    //jeśli pracownik nie ma żadnych wykonanych wizyt w danym dniu
                    if ($details == []){
                       $details = array(["Name" => $worker->getName(),"Surname" => $worker->getSurname(), "visitsCount" =>"0", "timeSum" => "0","income" => "0"]);
                    }

                    array_push($first_data, $date + $details);
                }
                array_push($second_data, $first_data);
                unset($first_data);
            }

            // Zsumowanie dla wszystkich pracowników
            $end_data = array();
            foreach($allWorkers as $worker){

                $query = $entityManager->createQuery('SELECT COUNT(a.id) as allVisitsCount, SUM(b.time) AS allTimeSum, SUM(b.price) AS allIncome FROM App\Entity\Visit a , App\Entity\Offer b ,  App\Entity\Worker c WHERE a.offer = b.id  AND a.worker = c.id AND a.worker = ?2 AND a.date >= ?1 AND a.date <= ?3 AND a.status = false AND a.canceled = false');

                $query->setParameter(1, $date_start);
                $query->setParameter(2, $worker);
                $query->setParameter(3, $date_end);

                $details = $query->getResult();

                //jeśli w zadanym okresie czasu nie było wizyt
                if ($details == [] || $details['0']['allVisitsCount'] == 0){
                   $details = array(["allVisitsCount" =>"0", "allTimeSum" => "0","allIncome" => "0"]);
                }

                array_push($end_data, $details);
            }
           return $this->json(['details'=>$second_data, 'workerStat'=>$end_data]);
        }
    }
}
