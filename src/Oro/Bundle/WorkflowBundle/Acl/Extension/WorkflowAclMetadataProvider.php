<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowAclMetadataProvider
{
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
     * @param array $workflowConfiguration
     *
     * @return array
     */
    protected function loadWorkflowTransitions(array $workflowConfiguration)
    {
        $transitions = [];
        if (isset($workflowConfiguration['transitions']) && is_array($workflowConfiguration['transitions'])) {
            foreach ($workflowConfiguration['transitions'] as $transitionName => $transitionRow) {
                $description = null;
                if (!empty($transitionRow['step_to'])
                    && !empty($workflowConfiguration['steps'][$transitionRow['step_to']])
                ) {
                    $description = $this->translator->trans(
                        'oro.workflow.transition.description',
                        [
                            '%toStep%' => $this->transLabel(
                                $workflowConfiguration['steps'][$transitionRow['step_to']]['label']
                            )
                        ]
                    );
                }
                $transitions[] = new FieldSecurityMetadata(
                    $transitionName,
                    $this->transLabel($transitionRow['label']),
                    [],
                    $description
                );
            }
        }

        return $transitions;
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
        return $this->translator->trans($label, [], 'workflows');
    }
}
