<?php

namespace Oro\Bundle\EntityBundle\Layout\Extension\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class AbstractEntityDataProvider implements DataProviderInterface
{
    /** @var object[] */
    protected $data;

    /**
     * Full Entity name.
     *
     * @var string
     */
    protected $entityFQCN;

    /** @var string */
    protected $contextIdAlias;

    /** @var ManagerRegistry $registry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return string
     */
    protected function getEntityFQCN()
    {
        if (null === $this->entityFQCN) {
            throw new \RuntimeException("EntityFQCN should be specified.");
        }

        return $this->entityFQCN;
    }

    /**
     * @param string $entityFQCN
     */
    public function setEntityFQCN($entityFQCN)
    {
        $this->entityFQCN = $entityFQCN;
    }

    /**
     * @return string
     */
    protected function getContextIdAlias()
    {
        if (null === $this->contextIdAlias) {
            throw new \RuntimeException("ContextIdAlias should be specified.");
        }

        return $this->contextIdAlias;
    }

    /**
     * @param string $contextIdAlias
     */
    public function setContextIdAlias($contextIdAlias)
    {
        $this->contextIdAlias = $contextIdAlias;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        $entityFQCN = $this->getEntityFQCN();
        if (!isset($context[$this->getContextIdAlias()])) {
            throw new \RuntimeException(sprintf("Context[%s] should be specified.", $this->getContextIdAlias()));
        }
        $entityId = $context[$this->getContextIdAlias()];

        if (!isset($data[$entityId])) {
            $data[$entityId] = $this->registry
                ->getManagerForClass($entityFQCN)
                ->getRepository($entityFQCN)
                ->find($entityId);
        }

        return $data[$entityId];
    }
}
