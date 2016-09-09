<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;

class OwnerTreeListener implements ContainerAwareInterface
{
    /** @var array [class name => [[field name, ...], [association name, ...]], ...] */
    protected $securityClasses = [];

    /** @var OwnerTreeProviderInterface */
    protected $treeProvider;

    /** @var ContainerInterface */
    protected $container;

    /** @var bool */
    protected $isCacheOutdated = false;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ServiceLink $treeProviderLink
     *
     * @deprecated 1.8.0:2.1.0 use $container property instead
     */
    public function __construct(ServiceLink $treeProviderLink = null)
    {
        $this->treeProviderLink = $treeProviderLink;
    }

    /**
     * @param string   $class        The FQCN of an entity to be monitored
     * @param string[] $fields       The names of fields or to-one associations
     * @param string[] $associations The names of to-many associations
     */
    public function addSupportedClass($class, array $fields = [], array $associations = [])
    {
        $this->securityClasses[$class] = [$fields, $associations];
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if ($this->isCacheOutdated || !$this->securityClasses) {
            return;
        }

        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->isCacheOutdated =
            $this->checkInsertedOrDeletedEntities($uow->getScheduledEntityInsertions())
            || $this->checkInsertedOrDeletedEntities($uow->getScheduledEntityDeletions())
            || $this->checkUpdatedEntities($uow)
            || $this->checkToManyRelations($uow->getScheduledCollectionUpdates())
            || $this->checkToManyRelations($uow->getScheduledCollectionDeletions());

        if ($this->isCacheOutdated) {
            $this->getTreeProvider()->clear();
        }
    }

    /**
     * @param object[] $entities
     *
     * @return bool
     */
    protected function checkInsertedOrDeletedEntities($entities)
    {
        foreach ($entities as $entity) {
            if (isset($this->securityClasses[ClassUtils::getRealClass($entity)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return bool
     */
    protected function checkUpdatedEntities(UnitOfWork $uow)
    {
        $entities = $uow->getScheduledEntityUpdates();
        foreach ($entities as $entity) {
            $entityClass = ClassUtils::getRealClass($entity);
            if (!isset($this->securityClasses[$entityClass])) {
                continue;
            }

            list($fields) = $this->securityClasses[$entityClass];
            if ($fields) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (array_intersect(array_keys($changeSet), $fields)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param PersistentCollection[] $collections
     *
     * @return bool
     */
    protected function checkToManyRelations($collections)
    {
        foreach ($collections as $collection) {
            $entityClass = ClassUtils::getRealClass($collection->getOwner());
            if (!isset($this->securityClasses[$entityClass])) {
                continue;
            }

            list(, $associations) = $this->securityClasses[$entityClass];
            if ($associations) {
                $associationMapping = $collection->getMapping();
                if (in_array($associationMapping['fieldName'], $associations, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return OwnerTreeProviderInterface
     */
    protected function getTreeProvider()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface not injected');
        }

        if (!$this->treeProvider) {
            $this->treeProvider = $this->container->get('oro_security.ownership_tree_provider.chain');
        }

        return $this->treeProvider;
    }
}
