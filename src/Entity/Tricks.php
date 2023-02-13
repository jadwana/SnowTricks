<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TricksRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TricksRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Il existe déjà un trick avec ce nom')]

class Tricks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique : true)]
    #[Assert\NotBlank(message: 'Le titre de la figure ne peut pas être vide')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'tricks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categories $category = null;

    #[ORM\OneToMany(mappedBy: 'tricks', targetEntity: Medias::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $medias;

    #[ORM\OneToMany(mappedBy: 'trick', targetEntity: Comments::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'tricks', targetEntity: Videos::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $videos;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->videos = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCategory(): ?Categories
    {
        return $this->category;
    }

    public function setCategory(?Categories $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Medias>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedias(Medias $medias): self
    {
        if (!$this->medias->contains($medias)) {
            $this->medias->add($medias);
            $medias->setTricks($this);
        }

        return $this;
    }

    public function removeMedias(Medias $medias): self
    {
        if ($this->medias->removeElement($medias)) {
            // set the owning side to null (unless already changed)
            if ($medias->getTricks() === $this) {
                $medias->setTricks(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comments>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comments $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTrick($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTrick() === $this) {
                $comment->setTrick(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Videos>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Videos $video): self
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->setTricks($this);
        }

        return $this;
    }

    public function removeVideo(Videos $video): self
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getTricks() === $this) {
                $video->setTricks(null);
            }
        }

        return $this;
    }
}
