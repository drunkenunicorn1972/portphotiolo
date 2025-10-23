<?php

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function save(Photo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Photo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find recent public photos
     */
    public function findRecentPublic(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.viewPrivacy = :privacy')
            ->setParameter('privacy', 'public')
            ->orderBy('p.uploadedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find photos by album
     */
    public function findByAlbum(int $albumId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.albums', 'a')
            ->where('a.id = :albumId')
            ->setParameter('albumId', $albumId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAccessiblePhotos(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if (!$user) {
            // Not logged in, only show public photos
            $qb->where('p.requiredRole IS NULL');
        } else {
            $roles = $user->getRoles();
            $qb->where('p.requiredRole IS NULL')
                ->orWhere('p.requiredRole IN (:roles)')
                ->setParameter('roles', $roles);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
