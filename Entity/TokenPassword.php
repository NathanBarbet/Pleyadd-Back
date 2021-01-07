<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TokenPassword
 *
 * @ORM\Table(name="token_password", uniqueConstraints={@ORM\UniqueConstraint(name="uc_Token_Password_Token", columns={"Token"})}, indexes={@ORM\Index(name="idx_Token_Password_User_ID", columns={"User_ID"})})
 * @ORM\Entity(repositoryClass="App\Repository\TokenPasswordRepository")
 */
class TokenPassword
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
     * @var bool
     *
     * @ORM\Column(name="Is_use", type="boolean", nullable=false)
     */
    private $isUse = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="IP_User", type="string", length=255, nullable=false)
     */
    private $ipUser;

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

    public function getIsUse(): ?bool
    {
        return $this->isUse;
    }

    public function setIsUse(bool $isUse): self
    {
        $this->isUse = $isUse;

        return $this;
    }

    public function getIpUser(): ?string
    {
        return $this->ipUser;
    }

    public function setIpUser(string $ipUser): self
    {
        $this->ipUser = $ipUser;

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
