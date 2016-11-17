<?php

namespace Oro\Bundle\WorkflowBundle\Scope;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowScopeManager
{
    const SCOPE_TYPE = 'workflow_definition';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ScopeManager */
    protected $scopeManager;

    /** @var bool */
    protected $enabled = true;

    /**
     * @param ManagerRegistry $registry
     * @param ScopeManager $scopeManager
     */
    public function __construct(ManagerRegistry $registry, ScopeManager $scopeManager)
    {
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param bool $isEnabled
     */
    public function setEnabled($isEnabled = true)
    {
        $this->enabled = (bool)$isEnabled;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    public function updateScopes(WorkflowDefinition $workflowDefinition)
    {
        if (!$this->enabled) {
            return;
        }

        $contexts = $this->createScopeContexts($workflowDefinition->getScopesConfig());

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
     */
    protected function createScopeContexts(array $scopesConfig)
    {
        $entities = $this->scopeManager->getScopeEntities(self::SCOPE_TYPE);
        $contexts = [];

        foreach ($scopesConfig as $scope) {
            $context = [];

            foreach ($scope as $identifier => $entityId) {
                if (!isset($entities[$identifier])) {
                    throw new \RuntimeException(
                        sprintf('Unknown field name "%s" for scope type "%s".', $identifier, self::SCOPE_TYPE)
                    );
                }

                $entity = $this->getRepository($entities[$identifier])->find($entityId);
                if (!$entity) {
                    throw new \RuntimeException(
                        sprintf('Could not found entity "%s" with id "%d".', $entities[$identifier], $entityId)
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
