<?php

namespace App\Entity;

use App\Repository\WarzoneUserPalmaresRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneUserPalmares
 *
 * @ORM\Table(name="warzone_user_palmares")
 * @ORM\Entity(repositoryClass=WarzoneUserPalmaresRepository::class)
 */
class WarzoneUserPalmares
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
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_ID", referencedColumnName="ID")
     * })
     */
    private $user_id;

    /**
     * @var \WarzoneTournois
     *
     * @ORM\ManyToOne(targetEntity="WarzoneTournois")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Tournois_ID", referencedColumnName="ID")
     * })
     */
    private $tournois_id;

    /**
     * @var \WarzoneTournoisEquipe
     *
     * @ORM\ManyToOne(targetEntity="WarzoneTournoisEquipe")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Equipe_ID", referencedColumnName="ID")
     * })
     */
    private $equipe_id;

    /**
     * @var int
     *
     * @ORM\Column(name="Position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @var int
     *
     * @ORM\Column(name="Nombre_equipe", type="integer", nullable=false)
     */
    private $nombreEquipe;

    /**
     * @var int
     *
     * @ORM\Column(name="Nombre_kills", type="integer", nullable=false)
     */
    private $nombreKills;

    /**
     * @var int
     *
     * @ORM\Column(name="Part_top1", type="integer", nullable=false)
     */
    private $partTop1;

    /**
     * @var int
     *
     * @ORM\Column(name="Part_top3", type="integer", nullable=false)
     */
    private $partTop3;

    /**
     * @var int
     *
     * @ORM\Column(name="Part_top10", type="integer", nullable=false)
     */
    private $partTop10;

    /**
     * @var int
     *
     * @ORM\Column(name="Part_top15", type="integer", nullable=false)
     */
    private $partTop15;

    /**
     * @var int
     *
     * @ORM\Column(name="Part_top20", type="integer", nullable=false)
     */
    private $partTop20;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserid(): ?User
    {
        return $this->user_id;
    }

    public function setUserid(?User $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getTournoisid(): ?WarzoneTournois
    {
        return $this->tournois_id;
    }

    public function setTournoisid(?WarzoneTournois $tournois_id): self
    {
        $this->tournois_id = $tournois_id;

        return $this;
    }

    public function getEquipeid(): ?WarzoneTournoisEquipe
    {
        return $this->equipe_id;
    }

    public function setEquipeid(?WarzoneTournoisEquipe $equipe_id): self
    {
        $this->equipe_id = $equipe_id;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getNombreEquipe(): ?int
    {
        return $this->nombreEquipe;
    }

    public function setNombreEquipe(int $nombreEquipe): self
    {
        $this->nombreEquipe = $nombreEquipe;

        return $this;
    }

    public function getNombreKills(): ?int
    {
        return $this->nombreKills;
    }

    public function setNombreKills(int $nombreKills): self
    {
        $this->nombreKills = $nombreKills;

        return $this;
    }

    public function getPartTop1(): ?int
    {
        return $this->partTop1;
    }

    public function setPartTop1(int $partTop1): self
    {
        $this->partTop1 = $partTop1;

        return $this;
    }

    public function getPartTop3(): ?int
    {
        return $this->partTop3;
    }

    public function setPartTop3(int $partTop3): self
    {
        $this->partTop3 = $partTop3;

        return $this;
    }

    public function getPartTop10(): ?int
    {
        return $this->partTop10;
    }

    public function setPartTop10(int $partTop10): self
    {
        $this->partTop10 = $partTop10;

        return $this;
    }

    public function getPartTop15(): ?int
    {
        return $this->partTop15;
    }

    public function setPartTop15(int $partTop15): self
    {
        $this->partTop15 = $partTop15;

        return $this;
    }

    public function getPartTop20(): ?int
    {
        return $this->partTop20;
    }

    public function setPartTop20(int $partTop20): self
    {
        $this->partTop20 = $partTop20;

        return $this;
    }
}
