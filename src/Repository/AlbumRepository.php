<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    public function save(Album $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Album $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all albums with their photo count
     */
    public function findAllWithPhotoCount(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.photos', 'p')
            ->addSelect('p')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find albums by user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAccessibleAlbums(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('a');

        if (!$user) {
            // Not logged in, only show public albums
            $qb->where('a.requiredRole IS NULL');
        } else {
            $roles = $user->getRoles();
            $qb->where('a.requiredRole IS NULL')
                ->orWhere('a.requiredRole IN (:roles)')
                ->setParameter('roles', $roles);
        }

        return $qb->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
