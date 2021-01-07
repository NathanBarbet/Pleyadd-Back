<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EmailDisposable
 *
 * @ORM\Table(name="email_disposable")
 * @ORM\Entity(repositoryClass="App\Repository\EmailDisposableRepository")
 */
class EmailDisposable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=true, options={"default"="NULL"})
     */
    private $domain = 'NULL';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }
}
