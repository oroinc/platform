<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;

/**
 * Class OwnerTreeProvider
 * @package Oro\Bundle\SecurityBundle\Owner
 */
class OwnerTreeProvider
{
    const CACHE_KEY = 'data';

    /** @var EntityManager */
    protected $em;

    /** @var OwnerTree */
    protected $tree;

    /** @var CacheProvider */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param CacheProvider $cache
     */
    public function __construct(EntityManager $em, CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->em    = $em;
    }

    /**
     * Get ACL tree
     *
     * @return OwnerTree
     * @throws \Exception
     */
    public function getTree()
    {
        $this->ensureTreeLoaded();

        if ($this->tree === null) {
            throw new \Exception('ACL tree cache was not warmed');
        }

        return $this->tree;
    }

    /**
     * Clear the owner tree cache
     */
    public function clear()
    {
        $this->cache->deleteAll();
    }

    /**
     * Warmup owner tree cache
     */
    public function warmUpCache()
    {
        $this->ensureTreeLoaded();
    }

    /**
     * Makes sure that tree data are loaded and cached
     */
    protected function ensureTreeLoaded()
    {
        if ($this->tree === null) {
            $treeData = null;
            if ($this->cache) {
                $treeData = $this->cache->fetch(self::CACHE_KEY);
            }
            if ($treeData) {
                $this->tree = $treeData;
            } else {
                $this->loadTree();
            }
        }
    }

    /**
     * Loads tree data and save them in cache
     */
    protected function loadTree()
    {
        $treeData = new OwnerTree();
        if ($this->checkDatabase()) {
            $this->fillTree($treeData);
        }

        if ($this->cache) {
            $this->cache->save(self::CACHE_KEY, $treeData);
        }

        $this->tree = $treeData;
    }

    /**
     * @param OwnerTree $tree
     */
    protected function fillTree(OwnerTree $tree)
    {
        $users         = $this->em->getRepository('Oro\Bundle\UserBundle\Entity\User')->findAll();
        $businessUnits = $this->em->getRepository('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')->findAll();

        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit->getOrganization()) {
                /** @var \Oro\Bundle\OrganizationBundle\Entity\BusinessUnit $businessUnit */
                $tree->addBusinessUnit($businessUnit->getId(), $businessUnit->getOrganization()->getId());
                if ($businessUnit->getOwner()) {
                    $tree->addBusinessUnitRelation($businessUnit->getId(), $businessUnit->getOwner()->getId());
                }
            }
        }

        foreach ($users as $user) {
            /** @var \Oro\Bundle\UserBundle\Entity\User $user */
            $owner = $user->getOwner();
            $tree->addUser($user->getId(), $owner ? $owner->getId() : null);
            foreach ($user->getOrganizations() as $organization) {
                $tree->addUserOrganization($user->getId(), $organization->getId());
                foreach ($user->getBusinessUnits() as $businessUnit) {
                    $organizationId   = $organization->getId();
                    $buOrganizationId = $businessUnit->getOrganization()->getId();
                    if ($organizationId == $buOrganizationId) {
                        $tree->addUserBusinessUnit($user->getId(), $organizationId, $businessUnit->getId());
                    }
                }
            }
        }
    }

    /**
     * Check if user table exists in db
     *
     * @return bool
     */
    protected function checkDatabase()
    {
        $tableName = $this->em->getClassMetadata('Oro\Bundle\UserBundle\Entity\User')->getTableName();
        $result    = false;
        try {
            $conn = $this->em->getConnection();

            if (!$conn->isConnected()) {
                $this->em->getConnection()->connect();
            }

            $result = $conn->isConnected() && (bool)array_intersect(
                array($tableName),
                $this->em->getConnection()->getSchemaManager()->listTableNames()
            );
        } catch (\PDOException $e) {
        }

        return $result;
    }
}
