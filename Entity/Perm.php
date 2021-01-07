<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Perm
 *
 * @ORM\Table(name="perm", uniqueConstraints={@ORM\UniqueConstraint(name="uc_Perm_Rank", columns={"Rank"})})
 * @ORM\Entity(repositoryClass="App\Repository\PermRepository")
 */
class Perm
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
     * @ORM\Column(name="Rank", type="string", length=50, nullable=false)
     */
    private $rank;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRank(): ?string
    {
        return $this->rank;
    }

    public function setRank(string $rank): self
    {
        $this->rank = $rank;

        return $this;
    }
}
