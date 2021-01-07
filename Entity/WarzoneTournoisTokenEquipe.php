<?php

namespace App\Entity;

use App\Repository\WarzoneTournoisTokenEquipeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneTournoisTokenEquipe
 *
 * @ORM\Table(name="warzone_tournois_token_equipe")
 * @ORM\Entity(repositoryClass=WarzoneTournoisTokenEquipeRepository::class)
 */
class WarzoneTournoisTokenEquipe
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
     * @ORM\Column(name="Token", type="string", length=255, nullable=false)
     */
    private $token;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_create", type="datetime", nullable=false)
     */
    private $dateCreate;

   /**
     * @var string
     *
     * @ORM\Column(name="Is_use", type="string", nullable=false)
     */
    private $isUse;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_id_send", referencedColumnName="ID")
     * })
     */
    private $userSend;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_id_receive", referencedColumnName="ID")
     * })
     */
    private $userReceive;

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
     * @var \WarzoneTournoisEquipeList
     *
     * @ORM\ManyToOne(targetEntity="WarzoneTournoisEquipeList")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Equipe_list_ID", referencedColumnName="ID")
     * })
     */
    private $equipeList;

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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): self
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getIsUse(): ?string
    {
        return $this->isUse;
    }

    public function setIsUse(string $isUse): self
    {
        $this->isUse = $isUse;

        return $this;
    }

    public function getUserSend(): ?User
    {
        return $this->userSend;
    }

    public function setUserSend(?User $userSend): self
    {
        $this->userSend = $userSend;

        return $this;
    }

    public function getUserReceive(): ?User
    {
        return $this->userReceive;
    }

    public function setUserReceive(?User $userReceive): self
    {
        $this->userReceive = $userReceive;

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

    public function getEquipeList(): ?WarzoneTournoisEquipeList
    {
        return $this->equipeList;
    }

    public function setEquipeList(?WarzoneTournoisEquipeList $equipeList): self
    {
        $this->equipeList = $equipeList;

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
