<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class AddSearchReindexJob extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        /** @var User $user */
        $user = $em->getRepository('OroUserBundle:User')->createQueryBuilder('user')
            ->select('user')
            ->setMaxResults(1)
            ->orderBy('user.id')
            ->getQuery()
            ->getOneOrNullResult();

        // if we have no user in result - we are in install process, so we does not need to reindex search data
        if (!$user) {
            return;
        }

        $searchResult = $this->getIndexer()->advancedSearch(
            sprintf(
                'from oro_user where username ~ %s and integer oro_user_owner = %d',
                $user->getUsername(),
                $user->getOwner()->getId()
            )
        );

        // if we have search result for username and it's owner - search data already contains data with owners.
        if ($searchResult->getRecordsCount()) {
            return;
        }

        $this->getSearchIndexer()->reindex();
    }

    /**
     * @return Indexer
     */
    protected function getIndexer()
    {
        return $this->container->get('oro_search.index');
    }

    /**
     * @return IndexerInterface
     */
    protected function getSearchIndexer()
    {
        return $this->container->get('oro_search.async.indexer');
    }
}
