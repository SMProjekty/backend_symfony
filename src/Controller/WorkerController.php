<?php

namespace App\Controller;

use App\Entity\Worker;
use App\Entity\Colors;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class WorkerController extends AbstractController
{
    #[Route('/worker', name: 'listOfWorkers', methods: 'GET')]
    public function getWorkerList(ManagerRegistry $doctrine): Response
    {
        $workers = $doctrine->getRepository(Worker::class)->findBy(array('active' => true));
        if ($workers == NULL)
            return new Response('No employees'); //NIE ZMIENIAĆ KOMUNIKATU
        return $this->json($workers, Response::HTTP_OK);
    }

    #[Route('/colors', name: 'listOfColors', methods: 'GET')]
    public function getColorsList(ManagerRegistry $doctrine): Response
    {
        $colors = $doctrine->getRepository(Colors::class)->findBy(array('isAvailable' => true));
        if ($colors == NULL)
            return new Response('No colors');
        return $this->json($colors, Response::HTTP_OK);
    }

    #[Route('/worker/delete/{id}', name: 'deleteWorker', methods: 'DELETE')]
    public function deleteWorker(ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $worker = $doctrine->getRepository(Worker::class)->findOneBy(array('id' => $id));
        $worker->setActive(false);
        $entityManager->flush();

        return $this->json('Successfully deleted worker.', Response::HTTP_OK);
    }

    #[Route('/worker/add', name: 'addWorker', methods: 'POST')]
    public function addWorker(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = $request->request->get('content');
        $data_post = json_decode($data_post, true);

        // $data_post_name = $request->get('name');
        // $data_post_surname = $request->get('surname');
        // $data_post_color = $request->get('color');

        $photo_post = $request->files->get('photo');
        $filename = pathinfo($photo_post->getClientOriginalName(), PATHINFO_FILENAME);
        $fullfilename = $filename .'.'. $photo_post->guessExtension();
        $photo_post->move($this->getParameter('photos_directory'), $fullfilename);

        //$color = $doctrine->getRepository(Colors::class)->findBy(array('code' => $data_post['color']));
        //if ($color != NULL) {
            //$color->setIsAvailable(false);
        //}

        $worker = new Worker();
        $worker->setName($data_post['name']);
        $worker->setSurname($data_post['surname']);
        $worker->setActive(true);
        $worker->setColor($data_post['color']);
        $worker->setPhoto($fullfilename);

        $entityManager->persist($worker);
        $entityManager->flush();

        return $this->json('Add new employee', Response::HTTP_OK);
    }

    #[Route('/worker/edit/{id}', name: 'editWorker', methods: 'PUT')]
    public function editWorker(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $data_post = $request->request->get('content');
        // dd($data_post);
        $data_post = json_decode($data_post, true);


        $worker = $doctrine->getRepository(Worker::class)->findOneBy(array('id' => $id));

        if ($data_post['name'] == NULL || $data_post['surname'] == NULL || $data_post['color'] == NULL) {
            return $this->json('Fields: name, surname and color cant be null.', Response::HTTP_OK);
        }

        $worker->setName($data_post['name']);
        $worker->setSurname($data_post['surname']);
        $worker->setColor($data_post['color']);

        if ($request->files->get('photo') != null) {
            $photo_post = $request->files->get('photo');
            //wziac aktualna nazwe zdjecia i usuniac z plikow, dodac na jego miejsdce nowe

            $filename = pathinfo($photo_post->getClientOriginalName(), PATHINFO_FILENAME);
            $fullfilename = $filename .'.'. $photo_post->guessExtension();
            $photo_post->move($this->getParameter('photos_directory'), $fullfilename);

            $worker->setPhoto($fullfilename);

            $entityManager->persist($worker);
            $entityManager->flush();
        }


        return $this->json('Edytowano', Response::HTTP_OK);
    }

    #[Route('/photo', name: 'uploadPhoto', methods: 'POST')]
    public function addPhoto(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $photo = $request->files->get('file');

        $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();
        $photo->move(
            $this->getParameter('photos_directory'),
            $newFilename
        );
    }

    #[Route('/photo/{filename}', name: 'getPhoto', methods: 'GET')]
    public function getPhoto(string $filename): Response
    {
        $publicResourcesFolderPath = $this->getParameter ('photos_directory');
        return new BinaryFileResponse($publicResourcesFolderPath.$filename);
    }
}