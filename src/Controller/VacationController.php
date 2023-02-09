<?php

namespace App\Controller;
use Date;
use DateTime;
use App\Entity\Vacation;
use App\Entity\Worker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class VacationController extends AbstractController
{
    #[Route('/vacation/{date}', name: 'get_vacations', methods: 'GET')]
    public function getVacation(ManagerRegistry $doctrine, string $date): Response
    {
        $entityManager = $doctrine->getManager();
        $date_start = new \DateTime($date);
        $date_stop = new \DateTime($date);
        $date_stop->modify('+ 1 month - 1 second');

        $query = $entityManager->createQuery('SELECT a FROM App\Entity\Vacation a WHERE (?1 > a.datefrom AND ?2< a.dateto) OR (?1 > a.datefrom AND ?2> a.datefrom AND ?1<a.dateto) OR (?1 < a.datefrom AND ?2> a.datefrom) OR (?1 < a.datefrom AND ?2> a.dateto)  ORDER BY a.datefrom');
        $query->setParameter(1, $date_start);
        $query->setParameter(2, $date_stop);
        $vacations = $query->getResult();

        return $this->json($vacations);
    }


    #[Route('/vacation/add', name: 'add_vacation', methods: 'POST')]
    public function addVacation(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);

        $worker = $doctrine->getRepository(Worker::class)->find($data_post['worker_id']);

        if ($worker == NULL)
            return $this->json('Fields: worker cant be null.', Response::HTTP_OK);
        if ($data_post['date_from'] == NULL || $data_post['date_to'] == NULL)
            return $this->json('Fields: date_from and date_to cant be null.', Response::HTTP_OK);

        //sprawdzenie czy urlop się nie nakłada
        $date_from =$data_post['date_from'];
        do{
            $query = $entityManager->createQuery('SELECT COUNT(a.id) as idVacation FROM App\Entity\Vacation a  WHERE a.worker = ?1 AND ?2 BETWEEN a.datefrom AND a.dateto');
            $query->setParameter(1, $data_post['worker_id']);
            $query->setParameter(2, $date_from);
            $result = $query->getResult();

            $date_from = date('Y-m-d', strtotime("+ 1 day", strtotime($date_from)));

             if($result['0']['idVacation'] != 0){
                return $this->json('Employee is already on leave', Response::HTTP_OK);
            }
        }
        while($date_from < $data_post['date_to']);

        //jeśli urlop się nie powtórzy
        if($result['0']['idVacation'] == 0)
        {
            $newVacation = new Vacation();
            $newVacation->setWorker($worker);
            $newVacation->setDatefrom(new \DateTime($data_post['date_from']));
            $newVacation->setDateto(new \DateTime($data_post['date_to']));

            $entityManager->persist($newVacation);
            $entityManager->flush();

            return $this->json('Leave added', Response::HTTP_OK);
        }
        return $this->json('Critical Error', Response::HTTP_OK);
    }


    #[Route('/vacation/delete/{id}', name: 'delete_vacation', methods: 'DELETE')]
    public function deleteVacation(ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $vacation = $doctrine->getRepository(Vacation::class)->findOneBy(array('id' => $id));

        if ($vacation == NULL) {
            return $this->json('Critical error', Response::HTTP_OK);
        }

        $entityManager->remove($vacation);
        $entityManager->flush();

        return $this->json('Successfully deleted vacation.', Response::HTTP_OK);
    }

    #[Route('/vacation/update/{id}', name: 'update_vacation', methods: 'PUT')]
    public function updateVacation(ManagerRegistry $doctrine, Request $request, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = json_decode($request->getContent(), true);
        $vacation = $doctrine->getRepository(Vacation::class)->findOneBy(array('id' => $id));

        if ($data_post['date_from'] == NULL || $data_post['date_to'] == NULL || $data_post['worker_id'] == NULL) {
            return $this->json('Fields: date_from, date_to and worker_id cant be null.', Response::HTTP_OK);
        }

        $date_from =$data_post['date_from'];
        do{
            $query = $entityManager->createQuery('SELECT COUNT(a.id) as idVacation FROM App\Entity\Vacation a  WHERE a.worker = ?1 AND ?2 BETWEEN a.datefrom AND a.dateto');
            $query->setParameter(1, $data_post['worker_id']);
            $query->setParameter(2, $date_from);
            $result = $query->getResult();

            $date_from = date('Y-m-d', strtotime("+ 1 day", strtotime($date_from)));

             if($result['0']['idVacation'] != 0){
                return $this->json('Employee is already on leave', Response::HTTP_OK);
            }
        }
        while($date_from < $data_post['date_to']);

        //jeśli urlop się nie powtórzy
        if($result['0']['idVacation'] == 0)
        {

            //$vacation>setWorker($worker);
            $vacation->setDatefrom(new \DateTime($data_post['date_from']));
            $vacation->setDateto(new \DateTime($data_post['date_to']));
            $entityManager->flush();

            return $this->json('Successfully updated offer.', Response::HTTP_OK);
        }
        return $this->json('Critical Error', Response::HTTP_OK);
    }

}
