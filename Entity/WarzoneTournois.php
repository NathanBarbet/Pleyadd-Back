<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneTournois
 *
 * @ORM\Table(name="warzone_tournois")
 *
 * @ORM\Entity(repositoryClass="App\Repository\WarzoneTournoisRepository")
 */
class WarzoneTournois
{
    /**
     * @var int
     *
     * @ORM\Column(name="ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="text")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="Type", type="text")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="Nombre", type="integer", nullable=false)
     */
    private $nombre;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_debut", type="datetime")
     */
    private $dateDebut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_fin", type="datetime")
     */
    private $dateFin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_fin_inscription", type="datetime")
     */
    private $dateFinInscription;

     /**
     * @var string|null
     *
     * @ORM\Column(name="Plateforme", type="string", length=255, nullable=true)
     */
    private $plateforme;

     /**
     * @var string
     *
     * @ORM\Column(name="Recompenses", type="text")
     */
    private $recompenses;

     /**
     * @var string
     *
     * @ORM\Column(name="Kdcap", type="string", length=255)
     */
    private $kdcap;

     /**
     * @var string|null
     *
     * @ORM\Column(name="Reglements", type="text", nullable=true)
     */
    private $reglements;

     /**
     * @var string
     *
     * @ORM\Column(name="Lien", type="string", length=255)
     */
    private $lien;

    /**
     * @var string
     *
     * @ORM\Column(name="Lien_mobile", type="string", length=255)
     */
    private $lienMobile;

    /**
     * @var bool
     *
     * @ORM\Column(name="Is_begin", type="boolean", nullable=false)
     */
    private $isBegin = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="Is_close", type="boolean", nullable=false)
     */
    private $isClose = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="Points_is_give", type="boolean", nullable=false)
     */
    private $pointsIsGive = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="Palmares_is_give", type="boolean", nullable=false)
     */
    private $palmaresIsGive = '0';

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Top_killer_user_ID", referencedColumnName="ID")
     * })
     */
    private $topKillerUser;

    /**
     * @var int
     *
     * @ORM\Column(name="Top_killer_kills", type="integer", length=11)
     */
    private $topKillerKills;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNombre(): ?int
    {
        return $this->nombre;
    }

    public function setNombre(int $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFinInscription(): ?\DateTimeInterface
    {
        return $this->dateFinInscription;
    }

    public function setDateFinInscription(\DateTimeInterface $dateFinInscription): self
    {
        $this->dateFinInscription = $dateFinInscription;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getPlateforme(): ?string
    {
        return $this->plateforme;
    }

    public function setPlateforme(?string $plateforme): self
    {
        $this->plateforme = $plateforme;

        return $this;
    }

    public function getRecompenses(): ?string
    {
        return $this->recompenses;
    }

    public function setRecompenses(string $recompenses): self
    {
        $this->recompenses = $recompenses;

        return $this;
    }

    public function getKdcap(): ?string
    {
        return $this->kdcap;
    }

    public function setKdcap(string $kdcap): self
    {
        $this->kdcap = $kdcap;

        return $this;
    }

    public function getReglements(): ?string
    {
        return $this->reglements;
    }

    public function setReglements(?string $reglements): self
    {
        $this->reglements = $reglements;

        return $this;
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

    public function getLienMobile(): ?string
    {
        return $this->lienMobile;
    }

    public function setLienMobile(string $lienMobile): self
    {
        $this->lienMobile = $lienMobile;

        return $this;
    }

    public function getIsClose(): ?bool
    {
        return $this->isClose;
    }

    public function setIsClose(bool $isClose): self
    {
        $this->isClose = $isClose;

        return $this;
    }

    public function getIsBegin(): ?bool
    {
        return $this->isBegin;
    }

    public function setIsBegin(bool $isBegin): self
    {
        $this->isBegin = $isBegin;

        return $this;
    }

    public function getPointsIsGive(): ?bool
    {
        return $this->pointsIsGive;
    }

    public function setPointsIsGive(bool $pointsIsGive): self
    {
        $this->pointsIsGive = $pointsIsGive;

        return $this;
    }

    public function getPalmaresIsGive(): ?bool
    {
        return $this->palmaresIsGive;
    }

    public function setPalmaresIsGive(bool $palmaresIsGive): self
    {
        $this->palmaresIsGive = $palmaresIsGive;

        return $this;
    }

    public function getTopKillerUser(): ?User
    {
        return $this->topKillerUser;
    }

    public function setTopKillerUser(?User $topKillerUser): self
    {
        $this->topKillerUser = $topKillerUser;

        return $this;
    }

    public function getTopKillerKills(): ?int
    {
        return $this->topKillerKills;
    }

    public function setTopKillerKills(int $topKillerKills): self
    {
        $this->topKillerKills = $topKillerKills;

        return $this;
    }
}
