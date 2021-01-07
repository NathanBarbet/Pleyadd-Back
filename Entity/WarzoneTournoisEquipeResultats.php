<?php

namespace App\Entity;

use App\Repository\WarzoneTournoisEquipeResultatsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneTournoisEquipeResultats
 *
 * @ORM\Table(name="warzone_tournois_equipe_resultats")
 * @ORM\Entity(repositoryClass=WarzoneTournoisEquipeResultatsRepository::class)
 */
class WarzoneTournoisEquipeResultats
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
     * @var \WarzoneTournois
     *
     * @ORM\ManyToOne(targetEntity="WarzoneTournois")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Tournois_ID", referencedColumnName="ID")
     * })
     */
    private $tournois;

    /**
     * @var string
     *
     * @ORM\Column(name="Partie", type="string", nullable=false)
     */
    private $partie;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_1_ID", referencedColumnName="ID")
     * })
     */
    private $user1;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_2_ID", referencedColumnName="ID")
     * })
     */
    private $user2;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_3_ID", referencedColumnName="ID")
     * })
     */
    private $user3;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_4_ID", referencedColumnName="ID")
     * })
     */
    private $user4;

    /**
     * @var string
     *
     * @ORM\Column(name="User_1_kills", type="string", nullable=false)
     */
    private $userKills1;

    /**
     * @var string
     *
     * @ORM\Column(name="User_2_kills", type="string", nullable=false)
     */
    private $userKills2;

    /**
     * @var string
     *
     * @ORM\Column(name="User_3_kills", type="string", nullable=false)
     */
    private $userKills3;

    /**
     * @var string
     *
     * @ORM\Column(name="User_4_kills", type="string", nullable=false)
     */
    private $userKills4;

    /**
     * @var string
     *
     * @ORM\Column(name="Position", type="string", nullable=false)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="Score", type="string", nullable=false)
     */
    private $score;

    /**
     * @var bool
     *
     * @ORM\Column(name="Is_valide", type="boolean", nullable=false)
     */
    private $isValide = '0';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTournois(): ?WarzoneTournois
    {
        return $this->tournois;
    }

    public function setTournois(?WarzoneTournois $tournois): self
    {
        $this->tournois = $tournois;

        return $this;
    }

    public function getPartie(): ?string
    {
        return $this->partie;
    }

    public function setPartie(string $partie): self
    {
        $this->partie = $partie;

        return $this;
    }

    public function getUser1(): ?User
    {
        return $this->user1;
    }

    public function setUser1(?User $user1): self
    {
        $this->user1 = $user1;

        return $this;
    }

    public function getUser2(): ?User
    {
        return $this->user2;
    }

    public function setUser2(?User $user2): self
    {
        $this->user2 = $user2;

        return $this;
    }

    public function getUser3(): ?User
    {
        return $this->user3;
    }

    public function setUser3(?User $user3): self
    {
        $this->user3 = $user3;

        return $this;
    }

    public function getUser4(): ?User
    {
        return $this->user4;
    }

    public function setUser4(?User $user4): self
    {
        $this->user4 = $user4;

        return $this;
    }

    public function getUserKills1(): ?string
    {
        return $this->userKills1;
    }

    public function setUserKills1(string $userKills1): self
    {
        $this->userKills1 = $userKills1;

        return $this;
    }

    public function getUserKills2(): ?string
    {
        return $this->userKills2;
    }

    public function setUserKills2(string $userKills2): self
    {
        $this->userKills2 = $userKills2;

        return $this;
    }

    public function getUserKills3(): ?string
    {
        return $this->userKills3;
    }

    public function setUserKills3(string $userKills3): self
    {
        $this->userKills3 = $userKills3;

        return $this;
    }

    public function getUserKills4(): ?string
    {
        return $this->userKills4;
    }

    public function setUserKills4(string $userKills4): self
    {
        $this->userKills4 = $userKills4;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(string $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getIsValide(): ?bool
    {
        return $this->isValide;
    }

    public function setIsValide(bool $isValide): self
    {
        $this->isValide = $isValide;

        return $this;
    }
}
