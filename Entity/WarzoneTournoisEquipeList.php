<?php

namespace App\Entity;

use App\Repository\WarzoneTournoisEquipeListRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneTournoisEquipeList
 *
 * @ORM\Table(name="warzone_tournois_equipe_list")
 * @ORM\Entity(repositoryClass=WarzoneTournoisEquipeListRepository::class)
 */
class WarzoneTournoisEquipeList
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
     * @var \WarzoneTournoisEquipe
     *
     * @ORM\ManyToOne(targetEntity="WarzoneTournoisEquipe")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Equipe_ID", referencedColumnName="ID")
     * })
     */
    private $equipe;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_ID", referencedColumnName="ID")
     * })
     */
    private $user;


    /**
     * @var bool
     *
     * @ORM\Column(name="Lead", type="boolean", nullable=false)
     */
    private $lead;

    /**
     * @var string
     *
     * @ORM\Column(name="Is_valide", type="string", nullable=false)
     */
    private $isValide;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEquipe(): ?WarzoneTournoisEquipe
    {
        return $this->equipe;
    }

    public function setEquipe(?WarzoneTournoisEquipe $equipe): self
    {
        $this->equipe = $equipe;

        return $this;
    }

    public function getLead(): ?bool
    {
        return $this->lead;
    }

    public function setLead(bool $lead): self
    {
        $this->lead = $lead;

        return $this;
    }

    public function getIsValide(): ?string
    {
        return $this->isValide;
    }

    public function setIsValide(string $isValide): self
    {
        $this->isValide = $isValide;

        return $this;
    }
}
