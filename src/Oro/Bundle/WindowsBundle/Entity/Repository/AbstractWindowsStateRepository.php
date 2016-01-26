<?php

namespace Oro\Bundle\WindowsBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractWindowsStateRepository extends EntityRepository
{
    /**
     * @param UserInterface $user
     * @param int $windowId
     * @return mixed
     */
    public function delete(UserInterface $user, $windowId)
    {
        $qb = $this->createQueryBuilder('w');

        return $qb
            ->delete()
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('w.user', ':user'),
                    $qb->expr()->eq('w.id', ':id')
                )
            )
            ->setParameter('id', $windowId)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * @param UserInterface $user
     * @param int $windowId
     * @param array $data
     * @return mixed
     */
    public function update(UserInterface $user, $windowId, array $data)
    {
        $connection = $this->getEntityManager()->getConnection();

        $qb = $this->createQueryBuilder('w');

        return $qb
            ->update()
            ->set('w.data', ':data')
            ->set('w.updatedAt', ':updatedAt')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('w.user', ':user'),
                    $qb->expr()->eq('w.id', ':id')
                )
            )
            ->setParameter('data', $connection->convertToDatabaseValue($data, Type::JSON_ARRAY))
            ->setParameter(
                'updatedAt',
                $connection->convertToDatabaseValue(new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME)
            )
            ->setParameter('id', $windowId)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
