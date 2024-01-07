<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds search reindex job to queue if it's not already added.
 */
class AddSearchReindexJob extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var User|null $user */
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

        $searchResult = $this->container->get('oro_search.index')->advancedSearch(
            sprintf(
                'from oro_user where username ~ %s and integer oro_user_owner = %d',
                $user->getUserIdentifier(),
                $user->getOwner()->getId()
            )
        );

        // if we have search result for username and it's owner - search data already contains data with owners.
        if ($searchResult->getRecordsCount()) {
            return;
        }

        $this->container->get('oro_search.async.indexer')->reindex();
    }
}
