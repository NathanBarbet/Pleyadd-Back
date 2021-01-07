<?php

namespace App\Entity;

use App\Repository\WarzoneUserPointRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WarzoneUserPoint
 *
 * @ORM\Table(name="warzone_user_point", indexes={@ORM\Index(name="idx_user_point_user_id", columns={"User_ID"}), @ORM\Index(name="idx_user_point_user_give", columns={"User_give"})})
 * @ORM\Entity(repositoryClass=WarzoneUserPointRepository::class)
 */
class WarzoneUserPoint
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
     * @var int
     *
     * @ORM\Column(name="Point", type="integer", nullable=false)
     */
    private $point;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_give", type="datetime", nullable=false)
     */
    private $date_give;

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
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="User_give", referencedColumnName="ID")
     * })
     */
    private $user_give;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoint(): ?int
    {
        return $this->point;
    }

    public function setPoint(int $point): self
    {
        $this->point = $point;

        return $this;
    }

    public function getDategive(): ?\DateTimeInterface
    {
        return $this->date_give;
    }

    public function setDategive(\DateTimeInterface $date_give): self
    {
        $this->date_give = $date_give;

        return $this;
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

    public function getUsergive(): ?User
    {
        return $this->user_give;
    }

    public function setUsergive(?User $user_give): self
    {
        $this->user_give = $user_give;

        return $this;
    }
}
