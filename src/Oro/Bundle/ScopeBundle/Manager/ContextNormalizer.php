<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

class ContextNormalizer
{
    /** @var ScopeManager */
    protected $scopeManager;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ScopeManager    $scopeManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ScopeManager $scopeManager, ManagerRegistry $registry)
    {
        $this->scopeManager = $scopeManager;
        $this->registry = $registry;
    }

    /**
     * Normalization using this method can be applied only to array context
     *
     * @param array $context
     * @return string[]
     */
    public function normalizeContext(array $context)
    {
        array_walk(
            $context,
            function (&$entityId) {
                if (is_object($entityId) && method_exists($entityId, 'getId')) {
                    $entityId = $entityId->getId();
                }
            }
        );

        return $context;
    }

    /**
     * Denormalization using this method can be applied only to array context
     *
     * @param string $scopeType
     * @param array  $context
     * @return object[]
     * @throws \LogicException
     */
    public function denormalizeContext($scopeType, array $context)
    {
        $entities = $this->scopeManager->getScopeEntities($scopeType);
        foreach ($context as $identifier => $entityId) {
            if (!array_key_exists($identifier, $entities)) {
                continue;
            }
            $entity = $this->registry
                ->getManagerForClass($entities[$identifier])
                ->find($entities[$identifier], $entityId);

            if (null === $entity) {
                throw new \LogicException(
                    sprintf('Entity %s with identifier %s does not exist.', $identifier, $entityId)
                );
            }
            $context[$identifier] = $entity;
        }

        return $context;
    }
}
