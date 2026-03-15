<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'reviews')]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: 'El libro es obligatorio')]
    private ?Book $book = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La calificación es obligatoria')]
    #[Assert\Type(
        type: 'integer',
        message: 'La calificación debe ser un número entero'
    )]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: 'La calificación debe estar entre {{ min }} y {{ max }}'
    )]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'El comentario es obligatorio')]
    #[Assert\Length(
        min: 1,
        minMessage: 'El comentario no puede estar vacío'
    )]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->created_at === null) {
            $this->created_at = new \DateTime();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }
}