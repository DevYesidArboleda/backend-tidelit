<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'El título es obligatorio')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'El título debe tener al menos {{ limit }} caracteres',
        maxMessage: 'El título no puede superar {{ limit }} caracteres'
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'El autor es obligatorio')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'El autor debe tener al menos {{ limit }} caracteres',
        maxMessage: 'El autor no puede superar {{ limit }} caracteres'
    )]
    private ?string $author = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'El año de publicación es obligatorio')]
    #[Assert\Type(
        type: 'integer',
        message: 'El año debe ser un número entero'
    )]
    #[Assert\Range(
        min: 1000,
        max: 2100,
        notInRangeMessage: 'El año debe estar entre {{ min }} y {{ max }}'
    )]
    private ?int $published_year = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book', orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getPublishedYear(): ?int
    {
        return $this->published_year;
    }

    public function setPublishedYear(int $published_year): static
    {
        $this->published_year = $published_year;
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setBook($this);
        }
        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getBook() === $this) {
                $review->setBook(null);
            }
        }
        return $this;
    }
}