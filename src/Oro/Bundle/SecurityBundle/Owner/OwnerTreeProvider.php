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

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var OwnershipMetadataProvider
     */
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
     * @return OwnerTree
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
     * @return OwnerTree
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
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillTree(OwnerTreeInterface $tree)
    {
        $userClass = $this->getOwnershipMetadataProvider()->getBasicLevelClass();
        $businessUnitClass = $this->getOwnershipMetadataProvider()->getLocalLevelClass();

        /** @var User[] $users */
        $users = $this->getManagerForClass($userClass)->getRepository($userClass)->findAll();

        /** @var BusinessUnit[] $businessUnits */
        $businessUnits = $this->getManagerForClass($businessUnitClass)->getRepository($businessUnitClass)->findAll();

        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit->getOrganization()) {
                $tree->addLocalEntity($businessUnit->getId(), $businessUnit->getOrganization()->getId());
                if ($businessUnit->getOwner()) {
                    $tree->addDeepEntity($businessUnit->getId(), $businessUnit->getOwner()->getId());
                }
            }
        }

        foreach ($users as $user) {
            $owner = $user->getOwner();
            $tree->addBasicEntity($user->getId(), $owner ? $owner->getId() : null);
            foreach ($user->getOrganizations() as $organization) {
                $organizationId = $organization->getId();
                $tree->addGlobalEntity($user->getId(), $organizationId);
                foreach ($user->getBusinessUnits() as $businessUnit) {
                    $buOrganizationId = $businessUnit->getOrganization()->getId();
                    if ($organizationId == $buOrganizationId) {
                        $tree->addLocalEntityToBasic($user->getId(), $businessUnit->getId(), $organizationId);
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
