<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\Table(name: 'photos')]
#[ORM\HasLifecycleCallbacks]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $viewCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $copyright = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $device = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?string $aperture = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 1, nullable: true)]
    private ?string $focalLength = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $exposureTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $iso = null;

    #[ORM\Column]
    private bool $flash = false;

    #[ORM\Column(length: 20)]
    private string $viewPrivacy = 'public';

    #[ORM\Column]
    private int $likeCount = 0;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $rating = null;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'photos')]
    private Collection $albums;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'photos')]
    #[ORM\JoinTable(name: 'photo_tags')]
    private Collection $tags;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->albums = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->uploadedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setUploadedAtValue(): void
    {
        if ($this->uploadedAt === null) {
            $this->uploadedAt = new \DateTimeImmutable();
        }
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount++;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCopyright(?string $copyright): static
    {
        $this->copyright = $copyright;
        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): static
    {
        $this->device = $device;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getAperture(): ?string
    {
        return $this->aperture;
    }

    public function setAperture(?string $aperture): static
    {
        $this->aperture = $aperture;
        return $this;
    }

    public function getFocalLength(): ?string
    {
        return $this->focalLength;
    }

    public function setFocalLength(?string $focalLength): static
    {
        $this->focalLength = $focalLength;
        return $this;
    }

    public function getExposureTime(): ?string
    {
        return $this->exposureTime;
    }

    public function setExposureTime(?string $exposureTime): static
    {
        $this->exposureTime = $exposureTime;
        return $this;
    }

    public function getIso(): ?int
    {
        return $this->iso;
    }

    public function setIso(?int $iso): static
    {
        $this->iso = $iso;
        return $this;
    }

    public function isFlash(): bool
    {
        return $this->flash;
    }

    public function setFlash(bool $flash): static
    {
        $this->flash = $flash;
        return $this;
    }

    public function getViewPrivacy(): string
    {
        return $this->viewPrivacy;
    }

    public function setViewPrivacy(string $viewPrivacy): static
    {
        if (!in_array($viewPrivacy, ['public', 'friend', 'family', 'private'])) {
            throw new \InvalidArgumentException('Invalid privacy level');
        }
        $this->viewPrivacy = $viewPrivacy;
        return $this;
    }

    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount): static
    {
        $this->likeCount = $likeCount;
        return $this;
    }

    public function incrementLikeCount(): static
    {
        $this->likeCount++;
        return $this;
    }

    public function decrementLikeCount(): static
    {
        if ($this->likeCount > 0) {
            $this->likeCount--;
        }
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->addPhoto($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            $album->removePhoto($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
