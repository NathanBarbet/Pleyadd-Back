<?php

namespace App\Entity;

use App\Repository\WarzoneTournoisEquipeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneTournoisEquipe
 *
 * @ORM\Table(name="warzone_tournois_equipe")
 * @ORM\Entity(repositoryClass=WarzoneTournoisEquipeRepository::class)
 */
class WarzoneTournoisEquipe
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
     * @ORM\Column(name="Nom", type="string", nullable=false)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="Elo", type="string", nullable=true)
     */
    private $elo;

    /**
     * @var \WarzoneTournois
     *
     * @ORM\ManyToOne(targetEntity="WarzoneTournois")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Tournois_ID", referencedColumnName="ID")
     * })
     */
    private $tournois;


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

    public function getElo(): ?string
    {
        return $this->elo;
    }

    public function setElo(string $elo): self
    {
        $this->elo = $elo;

        return $this;
    }

    public function getTournois(): ?WarzoneTournois
    {
        return $this->tournois;
    }

    public function setTournois(?WarzoneTournois $tournois): self
    {
        $this->tournois = $tournois;

        return $this;
    }
}
