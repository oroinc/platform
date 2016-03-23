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
        $users = $this->getManagerForClass($userClass)->getRepository($userClass)
            ->createQueryBuilder('u')
            ->leftJoin('u.organizations', 'uo')
            ->leftJoin('u.businessUnits', 'bu')
            ->leftJoin('bu.organization', 'bo')
            ->select([
                'u.id',
                'IDENTITY(u.organization) organization',
                'IDENTITY(u.owner) owner',
                "GROUP_CONCAT(DISTINCT uo.id)  organizations",
                "GROUP_CONCAT(
                    bu.id, ',', bo.id
                    SEPARATOR ';'
                )  business_units"  // pairs - business_unit_id and business unit organization_id

            ])
            ->groupBy('u.id')
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
            ->getQuery()
            ->getArrayResult();

        foreach ($businessUnits as $businessUnit) {
            if (!empty($businessUnit['organization'])) {
                $tree->addLocalEntity($businessUnit['id'], (int)$businessUnit['organization']);
                if ($businessUnit['owner']) {
                    $tree->addDeepEntity($businessUnit['id'], $businessUnit['owner']);
                }
            }
        }

        $tree->buildTree();

        foreach ($users as $user) {
            $owner = $user['owner'];
            $tree->addBasicEntity($user['id'], $owner ? $owner : null);
            $userOrganizations = explode(',', $user['organizations']);
            $userBusinessUnits = explode(';', $user['business_units']);

            foreach ($userOrganizations as $organizationId) {
                $tree->addGlobalEntity($user['id'], $organizationId);

                foreach ($userBusinessUnits as $businessUnit) {
                    $idPair = explode(',', $businessUnit); // pair of business_unit_id and business_unit_organization_id
                    $buId = $idPair[0];
                    $buOrganizationId = $idPair[1];
                    if ($organizationId == $buOrganizationId) {
                        $tree->addLocalEntityToBasic($user['id'], $buId, $organizationId);
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
