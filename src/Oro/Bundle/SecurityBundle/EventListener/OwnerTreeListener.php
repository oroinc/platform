<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class OwnerTreeListener implements ContainerAwareInterface
{
    /**
     * Array with classes need to be checked for
     *
     * @var array
     */
    protected $securityClasses = [];

    /**
     * @var OwnerTreeProviderInterface
     */
    protected $treeProvider;

    /**
     * @var bool
     */
    protected $needWarmup;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @return array
     */
    protected function getUserFieldsToIgnore()
    {
        return ['lastLogin', 'loginCount'];
    }

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
     * @param string $class
     */
    public function addSupportedClass($class)
    {
        if (!in_array($class, $this->securityClasses, true)) {
            $this->securityClasses[] = $class;
        }
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->securityClasses) {
            return;
        }

        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->needWarmup = $this->checkEntities($uow, $uow->getScheduledEntityInsertions())
            || $this->checkEntities($uow, $uow->getScheduledEntityUpdates())
            || $this->checkEntities($uow, $uow->getScheduledEntityDeletions());

        if ($this->needWarmup) {
            $this->getTreeProvider()->clear();
        }
    }

    /**
     * @param UnitOfWork $uow
     * @param array      $entities
     *
     * @return bool
     */
    protected function checkEntities(UnitOfWork $uow, array $entities)
    {
        $fieldsToIgnore = $this->getUserFieldsToIgnore();
        foreach ($entities as $entity) {
            if (in_array(ClassUtils::getRealClass($entity), $this->securityClasses, true)) {
                if ($entity instanceof UserInterface) {
                    $changeSet = $uow->getEntityChangeSet($entity);
                    if (!array_diff(array_keys($changeSet), $fieldsToIgnore)) {
                        continue;
                    }
                }

                return true;
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
