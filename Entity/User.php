<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Services\GetPoint;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="uc_User_Pseudo", columns={"Pseudo"})}, indexes={@ORM\Index(name="idx_User_Perm_ID", columns={"Perm_ID"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
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
     * @ORM\Column(name="Name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Firstname", type="string", length=50, nullable=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="Pseudo", type="string", length=50, nullable=false)
     */
    private $pseudo;

    /**
     * @var string
     *
     * @ORM\Column(name="Email", type="string", length=50, nullable=false)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Avatar", type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Banniere", type="string", length=255, nullable=true)
     */
    private $banniere;

    /**
     * @var string
     *
     * @ORM\Column(name="Password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="Dob", type="datetime", nullable=false)
     */
    private $dob;

    /**
     * @var string
     *
     * @ORM\Column(name="IP_user", type="string", length=255, nullable=false)
     */
    private $ipUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_register", type="datetime", nullable=false)
     */
    private $dateRegister;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Date_valide", type="datetime", nullable=true)
     */
    private $dateValide;

    /**
     * @var string
     *
     * @ORM\Column(name="Timezone", type="string", length=255, nullable=false)
     */
    private $timezone;

    /**
     * @var \Perm
     *
     * @ORM\ManyToOne(targetEntity="Perm")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Perm_ID", referencedColumnName="ID")
     * })
     */
    private $perm;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Bio", type="text", length=200, nullable=true)
     */
    private $bio;

    /**
     * @var string
     *
     * @ORM\Column(name="Clan", type="string", length=10, nullable=false)
     */
    private $clan;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Twitch", type="string", length=255, nullable=true)
     */
    private $twitch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Discord", type="string", length=255, nullable=true)
     */
    private $discord;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Steam", type="string", length=255, nullable=true)
     */
    private $steam;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Twitter", type="string", length=255, nullable=true)
     */
    private $twitter;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Youtube", type="string", length=255, nullable=true)
     */
    private $youtube;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Email2", type="string", length=255, nullable=true)
     */
    private $email2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Battlenet", type="string", length=255, nullable=true)
     */
    private $battlenet;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Psn", type="string", length=255, nullable=true)
     */
    private $psn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Xbox", type="string", length=255, nullable=true)
     */
    private $xbox;


    /**
     * @var string|null
     *
     * @ORM\Column(name="TRN", type="string", length=255, nullable=true)
     */
    private $trn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="Warzone_plateforme", type="string", length=255, nullable=true)
     */
    private $warzonePlateforme;

    /**
     *
     * @ORM\Column(name="Warzone_wins", type="string", length=255, nullable=true)
     */
    private $warzoneWins;

    /**
     *
     * @ORM\Column(name="Warzone_kills", type="string", length=255, nullable=true)
     */
    private $warzoneKills;

    /**
     *
     * @ORM\Column(name="Warzone_kdratio", type="string", length=255, nullable=true)
     */
    private $warzoneKdratio;

    /**
     *
     * @ORM\Column(name="Warzone_gamesplayed", type="string", length=255, nullable=true)
     */
    private $warzoneGamesplayed;

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function setUsername(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBanniere(): ?string
    {
        return $this->banniere;
    }

    public function setBanniere(?string $banniere): self
    {
        $this->banniere = $banniere;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): self
    {
        $this->dob = $dob;

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

    public function getDateRegister(): ?\DateTimeInterface
    {
        return $this->dateRegister;
    }

    public function setDateRegister(\DateTimeInterface $dateRegister): self
    {
        $this->dateRegister = $dateRegister;

        return $this;
    }

    public function getDateValide(): ?\DateTimeInterface
    {
        return $this->dateValide;
    }

    public function setDateValide(?\DateTimeInterface $dateValide): self
    {
        $this->dateValide = $dateValide;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getPerm(): ?Perm
    {
        return $this->perm;
    }

    public function setPerm(?Perm $perm): self
    {
        $this->perm = $perm;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    public function getClan(): ?string
    {
        return $this->clan;
    }

    public function setClan(string $clan): self
    {
        $this->clan = $clan;

        return $this;
    }

    public function getTwitch(): ?string
    {
        return $this->twitch;
    }

    public function setTwitch(?string $twitch): self
    {
        $this->twitch = $twitch;

        return $this;
    }

    public function getDiscord(): ?string
    {
        return $this->discord;
    }

    public function setDiscord(?string $discord): self
    {
        $this->discord = $discord;

        return $this;
    }

    public function getSteam(): ?string
    {
        return $this->steam;
    }

    public function setSteam(?string $steam): self
    {
        $this->steam = $steam;

        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): self
    {
        $this->twitter = $twitter;

        return $this;
    }

    public function getYoutube(): ?string
    {
        return $this->youtube;
    }

    public function setYoutube(?string $youtube): self
    {
        $this->youtube = $youtube;

        return $this;
    }

    public function getEmail2(): ?string
    {
        return $this->email2;
    }

    public function setEmail2(?string $email2): self
    {
        $this->email2 = $email2;

        return $this;
    }

    public function getBattlenet(): ?string
    {
        return $this->battlenet;
    }

    public function setBattlenet(?string $battlenet): self
    {
        $this->battlenet = $battlenet;

        return $this;
    }

    public function getPsn(): ?string
    {
        return $this->psn;
    }

    public function setPsn(?string $psn): self
    {
        $this->psn = $psn;

        return $this;
    }

    public function getXbox(): ?string
    {
        return $this->xbox;
    }

    public function setXbox(?string $xbox): self
    {
        $this->xbox = $xbox;

        return $this;
    }

    public function getTrn(): ?string
    {
        return $this->trn;
    }

    public function setTrn(?string $trn): self
    {
        $this->trn = $trn;

        return $this;
    }

    public function getWarzonePlateforme(): ?string
    {
        return $this->warzonePlateforme;
    }

    public function setWarzonePlateforme(?string $warzonePlateforme): self
    {
        $this->warzonePlateforme = $warzonePlateforme;

        return $this;
    }

    public function getWarzoneWins()
    {
        return $this->warzoneWins;
    }

    public function setWarzoneWins($warzoneWins): self
    {
        $this->warzoneWins = $warzoneWins;

        return $this;
    }

    public function getWarzoneKills()
    {
        return $this->warzoneKills;
    }

    public function setWarzoneKills($warzoneKills): self
    {
        $this->warzoneKills = $warzoneKills;

        return $this;
    }

    public function getWarzoneKdratio()
    {
        return $this->warzoneKdratio;
    }

    public function setWarzoneKdratio($warzoneKdratio): self
    {
        $this->warzoneKdratio = $warzoneKdratio;

        return $this;
    }

    public function getWarzoneGamesplayed()
    {
        return $this->warzoneGamesplayed;
    }

    public function setWarzoneGamesplayed($warzoneGamesplayed): self
    {
        $this->warzoneGamesplayed = $warzoneGamesplayed;

        return $this;
    }


    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
    }
}
