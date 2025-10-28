<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

#[ORM\Entity]
#[ORM\Table(name: 'error_log')]
class ErrorLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $serviceType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $scenario = null;

    #[ORM\Column(type: 'json', nullable: true )]
    private ?array $technicalContext = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stacktrace = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $datas = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $solution = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    // -------------------- Getters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function getScenario(): ?string
    {
        return $this->scenario;
    }

     public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function getTechnicalContext(): ?array
    {
        return $this->technicalContext;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getStacktrace(): ?string
    {
        return $this->stacktrace;
    }

    public function getDatas(): ?array
    {
        return $this->datas;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // -------------------- Setters --------------------

    public function setServiceType(string $serviceType): self
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setScenario(string $scenario): self
    {
        $this->scenario = $scenario;
        return $this;
    }

    public function setSolution(string $solution): self
    {
        $this->solution = $solution;
        return $this;
    }

    public function setTechnicalContext(array $technicalContext): self
    {
        $this->technicalContext = $technicalContext;
        return $this;
    }

    public function setStacktrace(string $stacktrace): self
    {
        $this->stacktrace = $stacktrace;
        return $this;
    }

    public function setDatas(?array $datas): self
    {
        $this->datas = $datas;
        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
