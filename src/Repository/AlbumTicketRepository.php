<?php

namespace App\Repository;

use App\Entity\AlbumTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class AlbumTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumTicket::class);
    }

    public function save(AlbumTicket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AlbumTicket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findValidTicketByUuid(string $uuid): ?AlbumTicket
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.uuid = :uuid')
            ->andWhere('t.isActive = :active')
            ->andWhere('(t.expiresAt IS NULL OR t.expiresAt > :now)')
            ->setParameter('uuid', $uuid)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
