<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Model\OwnershipProviderInterface;

class MenuUpdateProvider implements MenuUpdateProviderInterface
{
    /** @var string */
    protected $entityClass;

    /** @var OwnershipProviderInterface[] */
    private $ownershipProviders = [];

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdates($menu, $ownershipType)
    {
        /** @var MenuUpdateRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->getEntityClass());
        $ownershipProviders = $this->getOwnershipProviders($ownershipType);

        $menuUpdates = [];
        foreach ($ownershipProviders as $ownershipProvider) {
            $result = $repository->getMenuUpdates($menu, $ownershipProvider->getType(), $ownershipProvider->getId());
            $menuUpdates = array_merge($menuUpdates, $result);
        }
        return $menuUpdates;
    }

    public function addOwnershipProvider(OwnershipProviderInterface $provider, $priority)
    {
        $this->ownershipProviders[$priority][$provider->getType()] = $provider;
    }

    /**
     * Return ordered list of ownership providers started by $ownershipType
     * @param string $ownershipType
     * @return OwnershipProviderInterface[]
     */
    protected function getOwnershipProviders($ownershipType)
    {
        $ownershipProviders = [];
        // convert prioritised list to flat ordered list
        ksort($this->ownershipProviders, SORT_NUMERIC);
        foreach ($this->ownershipProviders as $list) {
            $ownershipProviders = array_merge($ownershipProviders, $list);
        }
        $key = array_search($ownershipType, array_keys($ownershipProviders), true);
        if ($key !== false) {
            return array_slice($ownershipProviders, $key, null, true);
        }

        return [];
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        if (!$this->entityClass) {
            throw new \UnexpectedValueException('Entity class should be defined');
        }

        return $this->entityClass;
    }

    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }
}
