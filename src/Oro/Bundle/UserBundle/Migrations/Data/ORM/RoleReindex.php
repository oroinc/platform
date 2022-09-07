<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Reindex user roles after "role" field was added to search index.
 */
class RoleReindex extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManagerForClass(Role::class);

        /** @var Role|null $role */
        $role = $em->getRepository(Role::class)->createQueryBuilder('role')
            ->select('role')
            ->where('role.role != :anonymous')
            ->setParameter('anonymous', User::ROLE_ANONYMOUS)
            ->setMaxResults(1)
            ->orderBy('role.id', 'desc')
            ->getQuery()
            ->getOneOrNullResult();

        // if we have no role in result - we are in install process, so we doesn't need to reindex search data
        if (null === $role) {
            return;
        }

        try {
            $searchResult = $this->getIndexer()
                ->advancedSearch(sprintf('from oro_access_role where role ~ %s', $role->getRole()));
            if ($searchResult->getRecordsCount()) {
                // data already up-to-date
                return;
            }
        } catch (\Exception $e) {
            // search index does not have roles yet
        }

        $this->getSearchIndexer()->reindex(Role::class);
    }

    private function getIndexer(): Indexer
    {
        return $this->container->get('oro_search.index');
    }

    /**
     * @return IndexerInterface
     */
    private function getSearchIndexer(): IndexerInterface
    {
        return $this->container->get('oro_search.async.indexer');
    }
}
