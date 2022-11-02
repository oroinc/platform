<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;

/**
 * The provider for workflow related security metadata.
 */
class WorkflowAclMetadataProvider
{
    private const STEPS               = 'steps';
    private const TRANSITIONS         = 'transitions';
    private const ALLOWED_TRANSITIONS = 'allowed_transitions';
    private const LABEL               = 'label';
    private const ORDER               = 'order';
    private const STEP_TO             = 'step_to';
    private const IS_START            = 'is_start';
    private const IS_START_STEP       = '_is_start';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var FeatureChecker */
    private $featureChecker;

    /** @var array|null */
    private $localCache;

    public function __construct(ManagerRegistry $doctrine, FeatureChecker $featureChecker)
    {
        $this->doctrine = $doctrine;
        $this->featureChecker = $featureChecker;
    }

    /**
     * @return WorkflowAclMetadata[]
     */
    public function getMetadata()
    {
        $this->ensureMetadataLoaded();

        return $this->localCache;
    }

    private function ensureMetadataLoaded()
    {
        if (null === $this->localCache) {
            $this->localCache = $this->loadMetadata();
        }
    }

    /**
     * @return WorkflowAclMetadata[]
     */
    private function loadMetadata()
    {
        $workflowRows = $this->getWorkflowEntityManager()
            ->getRepository(WorkflowDefinition::class)
            ->createQueryBuilder('w')
            ->select('w.name, w.label, w.configuration')
            ->getQuery()
            ->getArrayResult();

        $workflows = [];
        foreach ($workflowRows as $workflowRow) {
            $workflowName = $workflowRow['name'];
            if ($this->isWorkflowAccessible($workflowName)) {
                $workflows[] = new WorkflowAclMetadata(
                    $workflowRow['name'],
                    new WorkflowLabel($workflowRow['label']),
                    null,
                    $this->loadWorkflowTransitions($workflowRow['configuration'])
                );
            }
        }

        return $workflows;
    }

    /**
     * @param array $workflowConfig
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadWorkflowTransitions(array $workflowConfig)
    {
        $result = [];
        $steps = $this->getSteps($workflowConfig);
        $transitions = $this->getTransitions($workflowConfig);
        $addedStartTransitions = [];
        foreach ($steps as $stepName => $stepConfig) {
            if (!empty($stepConfig[self::ALLOWED_TRANSITIONS])) {
                $order = $this->getAttribute($stepConfig, self::ORDER, 0);
                foreach ($stepConfig[self::ALLOWED_TRANSITIONS] as $transitionName) {
                    if (isset($transitions[$transitionName])) {
                        $transitionConfig = $transitions[$transitionName];
                        $toStep = $this->getAttribute($transitionConfig, self::STEP_TO);
                        $isStartStep = $this->getAttribute($stepConfig, self::IS_START_STEP, false);
                        if ($isStartStep) {
                            $addedStartTransitions[$transitionName] = true;
                        }
                        $result[$order][] = new FieldSecurityMetadata(
                            $this->getTransitionIdentifier($transitionName, $isStartStep ? null : $stepName, $toStep),
                            $this->getTransitionLabel($workflowConfig, $transitionName, $stepName, $toStep)
                        );
                    }
                }
            }
        }
        foreach ($transitions as $transitionName => $transitionConfig) {
            if (!isset($addedStartTransitions[$transitionName])
                && (
                    $this->getAttribute($transitionConfig, self::IS_START, false)
                    || TransitionManager::DEFAULT_START_TRANSITION_NAME === $transitionName
                )
            ) {
                $toStep = $this->getAttribute($transitionConfig, self::STEP_TO);
                $result[-1][] = new FieldSecurityMetadata(
                    $this->getTransitionIdentifier($transitionName, null, $toStep),
                    $this->getStartTransitionLabel($workflowConfig, $transitionName, $toStep)
                );
            }
        }
        if (!empty($result)) {
            ksort($result);
            $result = array_merge(...array_values($result));
        }

        return $result;
    }

    /**
     * @param string $transitionName
     * @param string $fromStep
     * @param string $toStep
     *
     * @return string
     */
    private function getTransitionIdentifier($transitionName, $fromStep, $toStep)
    {
        return sprintf('%s|%s|%s', $transitionName, $fromStep, $toStep);
    }

    /**
     * @param array  $workflowConfig
     * @param string $transitionName
     * @param string $fromStep
     * @param string $toStep
     *
     * @return TransitionLabel
     */
    private function getTransitionLabel(array $workflowConfig, $transitionName, $fromStep, $toStep)
    {
        return new TransitionLabel(
            $this->getTransitionDefinitionLabel($workflowConfig, $transitionName),
            $this->getStepLabel($workflowConfig, $toStep),
            $this->getStepLabel($workflowConfig, $fromStep)
        );
    }

    /**
     * @param array  $workflowConfig
     * @param string $transitionName
     * @param string $toStep
     *
     * @return TransitionLabel
     */
    private function getStartTransitionLabel(array $workflowConfig, $transitionName, $toStep)
    {
        return new TransitionLabel(
            $this->getTransitionDefinitionLabel($workflowConfig, $transitionName),
            $this->getStepLabel($workflowConfig, $toStep)
        );
    }

    /**
     * @param array  $data
     * @param string $attributeName
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    private function getAttribute(array $data, $attributeName, $defaultValue = null)
    {
        $result = $defaultValue;
        if (!empty($data[$attributeName])) {
            $result = $data[$attributeName];
        }

        return $result;
    }

    /**
     * @param array $workflowConfig
     *
     * @return array
     */
    private function getSteps(array $workflowConfig)
    {
        if (isset($workflowConfig[self::STEPS]) && is_array($workflowConfig[self::STEPS])) {
            return $workflowConfig[self::STEPS];
        }

        return [];
    }

    /**
     * @param array $workflowConfig
     *
     * @return array
     */
    private function getTransitions(array $workflowConfig)
    {
        if (isset($workflowConfig[self::TRANSITIONS]) && is_array($workflowConfig[self::TRANSITIONS])) {
            return $workflowConfig[self::TRANSITIONS];
        }

        return [];
    }

    /**
     * @param array  $workflowConfig
     * @param string $transitionName
     *
     * @return string
     */
    private function getTransitionDefinitionLabel(array $workflowConfig, $transitionName)
    {
        return $workflowConfig[self::TRANSITIONS][$transitionName][self::LABEL];
    }

    /**
     * @param array  $workflowConfig
     * @param string $stepName
     *
     * @return string|null
     */
    private function getStepLabel(array $workflowConfig, $stepName)
    {
        if (!empty($workflowConfig[self::STEPS][$stepName])) {
            return $workflowConfig[self::STEPS][$stepName][self::LABEL];
        }

        return null;
    }

    /**
     * @return EntityManager
     */
    private function getWorkflowEntityManager()
    {
        return $this->doctrine->getManagerForClass(WorkflowDefinition::class);
    }

    /**
     * @param string $workflowName
     *
     * @return bool
     */
    private function isWorkflowAccessible($workflowName)
    {
        return $this->featureChecker->isResourceEnabled(
            $workflowName,
            FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
        );
    }
}
