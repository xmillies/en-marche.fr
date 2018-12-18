<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class Message implements AuthoredInterface
{
    use EntityIdentityTrait;
    use TimestampableEntity;

    /**
     * @var Adherent
     *
     * @ORM\ManyToOne(targetEntity="Adherent")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column
     *
     * @Assert\NotBlank
     * @Assert\Length(min="3", max="255")
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column
     *
     * @Assert\NotBlank
     * @Assert\Length(min="3", max="255")
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Assert\NotBlank
     * @Assert\Length(min="3", max="5000")
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $externalId;

    public function __construct(UuidInterface $uuid, Adherent $author)
    {
        $this->uuid = $uuid;
        $this->author = $author;
    }

    public static function createFromAdherent(Adherent $adherent): self
    {
        return new self(Uuid::uuid4(), $adherent);
    }

    public function getAuthor(): Adherent
    {
        return $this->author;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setAuthor(Adherent $author): void
    {
        $this->author = $author;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }
}
