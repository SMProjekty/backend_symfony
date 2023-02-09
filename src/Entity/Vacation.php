<?php

namespace App\Entity;

use App\Repository\VacationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VacationRepository::class)]
class Vacation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Worker $worker = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datefrom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateto = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorker(): ?Worker
    {
        return $this->worker;
    }

    public function setWorker(?Worker $worker): self
    {
        $this->worker = $worker;

        return $this;
    }

    public function getDatefrom(): ?\DateTimeInterface
    {
        return $this->datefrom;
    }

    public function setDatefrom(\DateTimeInterface $datefrom): self
    {
        $this->datefrom = $datefrom;

        return $this;
    }

    public function getDateto(): ?\DateTimeInterface
    {
        return $this->dateto;
    }

    public function setDateto(\DateTimeInterface $dateto): self
    {
        $this->dateto = $dateto;

        return $this;
    }
}
