<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

class WorkflowAclMetadataProvider
{
    const STEPS               = 'steps';
    const TRANSITIONS         = 'transitions';
    const ALLOWED_TRANSITIONS = 'allowed_transitions';
    const LABEL               = 'label';
    const ORDER               = 'order';
    const STEP_TO             = 'step_to';
    const IS_START            = 'is_start';
    const IS_START_STEP       = '_is_start';

    /** transition name (from step -> to step) */
    const TRANSITION_LABEL_TEMPLATE = "%s (%s \u{2192} %s)";

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FeatureChecker */
    protected $featureChecker;

    /** @var array|null */
    protected $localCache;

    /**
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     * @param FeatureChecker      $featureChecker
     */
    public function __construct(
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        FeatureChecker $featureChecker
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
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

    protected function ensureMetadataLoaded()
    {
        if (null === $this->localCache) {
            $this->localCache = $this->loadMetadata();
        }
    }

    /**
     * @return WorkflowAclMetadata[]
     */
    protected function loadMetadata()
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
                    $this->transLabel($workflowRow['label']),
                    '',
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
     */
    protected function loadWorkflowTransitions(array $workflowConfig)
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
                        if ($this->getAttribute($stepConfig, self::IS_START_STEP, false)) {
                            $addedStartTransitions[$transitionName] = true;
                        }
                        $result[$order][] = new FieldSecurityMetadata(
                            $this->getTransitionIdentifier($transitionName, $stepName, $toStep),
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
            $result = call_user_func_array('array_merge', $result);
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
    protected function getTransitionIdentifier($transitionName, $fromStep, $toStep)
    {
        return sprintf('%s|%s|%s', $transitionName, $fromStep, $toStep);
    }

    /**
     * @param array  $workflowConfig
     * @param string $transitionName
     * @param string $fromStep
     * @param string $toStep
     *
     * @return string
     */
    protected function getTransitionLabel(array $workflowConfig, $transitionName, $fromStep, $toStep)
    {
        return sprintf(
            self::TRANSITION_LABEL_TEMPLATE,
            $this->getTransitionDefinitionLabel($workflowConfig, $transitionName),
            $this->getStepLabel($workflowConfig, $fromStep),
            $this->getStepLabel($workflowConfig, $toStep)
        );
    }

    /**
     * @param array  $workflowConfig
     * @param string $transitionName
     * @param string $toStep
     *
     * @return string
     */
    protected function getStartTransitionLabel(array $workflowConfig, $transitionName, $toStep)
    {
        return sprintf(
            self::TRANSITION_LABEL_TEMPLATE,
            $this->getTransitionDefinitionLabel($workflowConfig, $transitionName),
            $this->translator->trans('(Start)', [], 'jsmessages'),
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
    protected function getAttribute(array $data, $attributeName, $defaultValue = null)
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
    protected function getSteps(array $workflowConfig)
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
    protected function getTransitions(array $workflowConfig)
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
     * @return string|null
     */
    protected function getTransitionDefinitionLabel(array $workflowConfig, $transitionName)
    {
        if (!empty($workflowConfig[self::TRANSITIONS][$transitionName])) {
            return $this->transLabel($workflowConfig[self::TRANSITIONS][$transitionName][self::LABEL]);
        }

        return null;
    }

    /**
     * @param array  $workflowConfig
     * @param string $stepName
     *
     * @return string|null
     */
    protected function getStepLabel(array $workflowConfig, $stepName)
    {
        if (!empty($workflowConfig[self::STEPS][$stepName])) {
            return $this->transLabel($workflowConfig[self::STEPS][$stepName][self::LABEL]);
        }

        return null;
    }

    /**
     * @return EntityManager
     */
    protected function getWorkflowEntityManager()
    {
        return $this->doctrine->getManagerForClass(WorkflowDefinition::class);
    }

    /**
     * @param string $workflowName
     *
     * @return bool
     */
    protected function isWorkflowAccessible($workflowName)
    {
        return $this->featureChecker->isResourceEnabled(
            $workflowName,
            FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
        );
    }

    /**
     * @param string $label
     *
     * @return string
     */
    protected function transLabel($label)
    {
        return $this->translator->trans($label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }
}
