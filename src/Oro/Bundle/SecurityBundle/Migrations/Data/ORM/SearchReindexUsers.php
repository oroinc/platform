<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Reindex search index for User entity in case if data does not contains info about assigned organizations.
 */
class SearchReindexUsers extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)
            ->createQueryBuilder('user')
            ->select('user')
            ->setMaxResults(1)
            ->orderBy('user.id')
            ->getQuery()
            ->getOneOrNullResult();

        // if we have no user in result - we are in install process, so we does not need to reindex search data
        if (!$user) {
            return;
        }

        $this->container->get('oro_search.async.indexer')->reindex(User::class);
    }
}
