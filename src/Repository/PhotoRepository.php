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

        // Return that what the user is allowed to see
        if (!$user) {
            // Not logged in, only show public photos
            $qb->where('p.viewPrivacy = :privacy')
                ->setParameter('privacy', 'public');
        } else {
            $userRoles = $user->getRoles();
            if (in_array('ROLE_USER', $userRoles) && !in_array('ROLE_ADMIN', $userRoles)) {
                $qb->where('p.viewPrivacy = :privacy1')
                    ->orWhere('p.viewPrivacy = :privacy2')
                    ->setParameter('privacy1', 'public')
                    ->setParameter('privacy2', 'member');
            }
            if (in_array('ROLE_FRIEND', $userRoles) && !in_array('ROLE_ADMIN', $userRoles)) {
                $qb->where('p.viewPrivacy = :privacy1')
                    ->orWhere('p.viewPrivacy = :privacy2')
                    ->orWhere('p.viewPrivacy = :privacy3')
                    ->setParameter('privacy1', 'public')
                    ->setParameter('privacy2', 'member')
                    ->setParameter('privacy3', 'friend');
            }
            if (in_array('ROLE_FAMILY', $userRoles) && !in_array('ROLE_ADMIN', $userRoles)) {
                $qb->where('p.viewPrivacy = :privacy1')
                    ->orWhere('p.viewPrivacy = :privacy2')
                    ->orWhere('p.viewPrivacy = :privacy3')
                    ->orWhere('p.viewPrivacy = :privacy4')
                    ->setParameter('privacy1', 'public')
                    ->setParameter('privacy2', 'member')
                    ->setParameter('privacy3', 'friend')
                    ->setParameter('privacy4', 'family');
            }
            if (in_array('ROLE_ADMIN', $userRoles)) {
                $qb->where('p.viewPrivacy = :privacy1')
                    ->orWhere('p.viewPrivacy = :privacy2')
                    ->orWhere('p.viewPrivacy = :privacy3')
                    ->orWhere('p.viewPrivacy = :privacy4')
                    ->orWhere('p.viewPrivacy = :privacy5')
                    ->setParameter('privacy1', 'public')
                    ->setParameter('privacy2', 'member')
                    ->setParameter('privacy3', 'friend')
                    ->setParameter('privacy4', 'family')
                    ->setParameter('privacy5', 'private');
            }
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
