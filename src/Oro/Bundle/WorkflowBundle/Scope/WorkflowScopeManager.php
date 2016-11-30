<?php

namespace Oro\Bundle\WorkflowBundle\Scope;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowScopeConfigurationException;

class WorkflowScopeManager
{
    const SCOPE_TYPE = 'workflow_definition';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ScopeManager */
    protected $scopeManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry $registry
     * @param ScopeManager $scopeManager
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $registry, ScopeManager $scopeManager, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
        $this->logger = $logger;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param bool $resetScopes
     * @throws WorkflowScopeConfigurationException
     */
    public function updateScopes(WorkflowDefinition $workflowDefinition, $resetScopes = false)
    {
        $contexts = [];

        try {
            if (!$resetScopes) {
                $contexts = $this->createScopeContexts($workflowDefinition->getScopesConfig());
            }
        } catch (WorkflowScopeConfigurationException $e) {
            $this->logger->error(
                '[WorkflowScopeManager] Workflow scopes could not be updated.',
                [
                    'worklflow' => $workflowDefinition->getName(),
                    'scope_configs' => $workflowDefinition->getScopesConfig(),
                    'exception' => $e
                ]
            );

            throw $e;
        }

        /** @var Scope[] $scopes */
        $scopes = new ArrayCollection();
        foreach ($contexts as $context) {
            $scopes->add($this->scopeManager->findOrCreate(self::SCOPE_TYPE, $context));
        }

        foreach ($this->getScopeDiff($workflowDefinition->getScopes(), $scopes) as $scope) {
            $workflowDefinition->removeScope($scope);
        }

        foreach ($this->getScopeDiff($scopes, $workflowDefinition->getScopes()) as $scope) {
            $workflowDefinition->addScope($scope);
        }

        $manager = $this->getObjectManager(WorkflowDefinition::class);
        $manager->flush();
    }

    /**
     * @param array $scopesConfig
     * @return array
     * @throws WorkflowScopeConfigurationException
     */
    protected function createScopeContexts(array $scopesConfig)
    {
        $entities = $this->scopeManager->getScopeEntities(self::SCOPE_TYPE);
        $contexts = [];

        foreach ($scopesConfig as $scope) {
            $context = [];

            foreach ($scope as $identifier => $entityId) {
                if (!isset($entities[$identifier])) {
                    throw new WorkflowScopeConfigurationException(
                        sprintf('Unknown field name "%s" for scope type "%s".', $identifier, self::SCOPE_TYPE)
                    );
                }

                $entity = $this->getRepository($entities[$identifier])->find($entityId);
                if (!$entity) {
                    throw new WorkflowScopeConfigurationException(
                        sprintf('Cannot find entity "%s" with id "%d".', $entities[$identifier], $entityId)
                    );
                }

                $context[$identifier] = $entity;
            }

            if ($context) {
                $contexts[] = $context;
            }
        }

        return $contexts;
    }

    /**
     * @param Collection|Scope[] $from
     * @param Collection|Scope[] $to
     * @return Collection|Scope[]
     */
    private function getScopeDiff(Collection $from, Collection $to)
    {
        $scopes = new ArrayCollection();

        foreach ($from as $fromScope) {
            $found = false;

            foreach ($to as $toScope) {
                if ($fromScope->getId() && $toScope->getId() && $fromScope->getId() === $toScope->getId()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $scopes->add($fromScope);
            }
        }

        return $scopes;
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getObjectManager($className)->getRepository($className);
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getObjectManager($className)
    {
        return $this->registry->getManagerForClass($className);
    }
}
