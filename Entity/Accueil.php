<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Accueil
 *
 * @ORM\Table(name="accueil", indexes={@ORM\Index(name="idx_Accueil_User_ID", columns={"User_ID"})})
 * @ORM\Entity(repositoryClass="App\Repository\AccueilRepository")
 */
class Accueil
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="ID", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Lien", type="string", length=255, nullable=false)
     */
    private $lien;

    /**
     * @var string
     *
     * @ORM\Column(name="Slider", type="string", nullable=false)
     */
    private $slider;

    /**
     * @var int
     *
     * @ORM\Column(name="Ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Href", type="string", length=255, nullable=true)
     */
    private $href;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_ID", referencedColumnName="ID")
     * })
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function setLien(string $lien): self
    {
        $this->lien = $lien;

        return $this;
    }

    public function getSlider(): ?string
    {
        return $this->slider;
    }

    public function setSlider(string $slider): self
    {
        $this->slider = $slider;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function setHref(string $href): self
    {
        $this->href = $href;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
