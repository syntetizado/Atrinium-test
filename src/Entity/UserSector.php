<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserSector
 *
 * @ORM\Table(name="user_sector", indexes={@ORM\Index(name="id_sector_fk_usersector", columns={"id_sector"}), @ORM\Index(name="id_user_fk_usersector", columns={"id_user"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserSectorRepository")
 */
class UserSector
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Sector
     *
     * @ORM\ManyToOne(targetEntity="Sector")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_sector", referencedColumnName="id")
     * })
     */
    private $idSector;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * })
     */
    private $idUser;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIdSector(): ?Sector
    {
        return $this->idSector;
    }

    public function setIdSector(?Sector $idSector): self
    {
        $this->idSector = $idSector;

        return $this;
    }

    public function getIdUser(): ?User
    {
        return $this->idUser;
    }

    public function setIdUser(?User $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }


}
