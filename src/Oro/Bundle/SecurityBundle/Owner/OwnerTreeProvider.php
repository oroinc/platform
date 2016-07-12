<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class OwnerTreeProvider
 * @package Oro\Bundle\SecurityBundle\Owner
 */
class OwnerTreeProvider extends AbstractOwnerTreeProvider
{
    /**
     * @deprecated 1.8.0:2.1.0 use AbstractOwnerTreeProvider::CACHE_KEY instead
     */
    const CACHE_KEY = 'data';

    /**
     * @var EntityManager
     *
     * @deprecated 1.8.0:2.1.0 use AbstractOwnerTreeProvider::getManagerForClass instead
     */
    protected $em;

    /** @var CacheProvider */
    private $cache;

    /** @var OwnershipMetadataProvider */
    private $ownershipMetadataProvider;

    /**
     * {@inheritdoc}
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getContainer()->get('oro_security.ownership_tree_provider.cache');
        }

        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTreeData()
    {
        return new OwnerTree();
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return parent::getTree();
    }

    /**
     * @param EntityManager $em
     * @param CacheProvider $cache
     *
     * @deprecated 1.8.0:2.1.0 use AbstractOwnerTreeProvider::getContainer instead
     */
    public function __construct(EntityManager $em, CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->em    = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillTree(OwnerTreeInterface $tree)
    {
        $userClass         = $this->getOwnershipMetadataProvider()->getBasicLevelClass();
        $businessUnitClass = $this->getOwnershipMetadataProvider()->getLocalLevelClass();

        /** @var User[] $users */

        $users = $this->getManagerForClass($userClass)
            ->getRepository($userClass)
            ->createQueryBuilder('u')
            ->leftJoin('u.owner', 'owner')
            ->leftJoin('u.organizations', 'organizations')
            ->leftJoin('u.businessUnits', 'bu')
            ->leftJoin('bu.organization', 'bu_organization')
            ->select(
                'partial u.{id}',
                'partial owner.{id}',
                'partial organizations.{id}',
                'partial bu.{id}',
                'partial bu_organization.{id}'
            )
            ->getQuery()
            ->getArrayResult();

        /** @var BusinessUnit[] $businessUnits */
        $businessUnitsRepo = $this->getManagerForClass($businessUnitClass)->getRepository($businessUnitClass);
        $businessUnits     = $businessUnitsRepo
            ->createQueryBuilder('bu')
            ->select([
                'bu.id',
                'IDENTITY(bu.organization) organization',
                'IDENTITY(bu.owner) owner' //aka parent business unit
            ])
            ->addSelect('(CASE WHEN bu.owner IS NULL THEN 0 ELSE 1 END) AS HIDDEN ORD')
            ->addOrderBy('ORD, owner', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($businessUnits as $businessUnit) {
            if (!empty($businessUnit['organization'])) {
                $tree->addLocalEntity($businessUnit['id'], (int)$businessUnit['organization']);
                $tree->addDeepEntity($businessUnit['id'], $businessUnit['owner']);
            }
        }

        $tree->buildTree();

        foreach ($users as $user) {
            $owner = $user['owner'];
            $tree->addBasicEntity($user['id'], isset($owner['id']) ? $owner['id'] : null);

            foreach ($user['organizations'] as $organization) {
                $tree->addGlobalEntity($user['id'], $organization['id']);

                foreach ($user['businessUnits'] as $businessUnit) {
                    if ($organization['id'] == $businessUnit['organization']['id']) {
                        $tree->addLocalEntityToBasic($user['id'], $businessUnit['id'], $organization['id']);
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadataProvider()
    {
        if (!$this->ownershipMetadataProvider) {
            $this->ownershipMetadataProvider = $this->getContainer()
                ->get('oro_security.owner.ownership_metadata_provider');
        }

        return $this->ownershipMetadataProvider;
    }
}
