<?php

namespace Oro\Bundle\WorkflowBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Translation\TranslatorInterface;


use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowReplacementSearchHandler extends SearchHandler
{
    const DELIMITER = ';';

    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName || !$this->workflowRegistry) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * @param WorkflowRegistry $workflowRegistry
     */
    public function setWorkflowRegistry(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (strpos($search, self::DELIMITER) === false) {
            return [];
        }

        list($searchTerm, $workflowName) = $this->explodeSearchTerm($search);

        /* @var $queryBuilder QueryBuilder */
        $queryBuilder = $this->entityRepository->createQueryBuilder('w');
        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        if ($searchTerm) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('w.label', ':search'))
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($workflowName) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->notIn('w.' . $this->idFieldName, ':id'))
                ->setParameter('id', $this->getWorkflowNamesForExclusion($workflowName));
        }

        $workflows = $queryBuilder->getQuery()->getResult();

        return array_filter(
            $workflows,
            function (WorkflowDefinition $definition) {
                $isActive = $definition->isActive();
                if ($isActive) {
                    $definition->setLabel(
                        $this->translator->trans(
                            $definition->getLabel(),
                            [],
                            WorkflowTranslationHelper::TRANSLATION_DOMAIN
                        )
                    );
                }

                return $isActive;
            }
        );
    }

    /**
     * @param string $search
     * @return array
     */
    protected function explodeSearchTerm($search)
    {
        $delimiterPos = strrpos($search, self::DELIMITER);
        $searchTerm = substr($search, 0, $delimiterPos);
        $workflowName = substr($search, $delimiterPos + 1);

        return [$searchTerm, (string) $workflowName];
    }

    /**
     * @param string $workflowName
     * @return array
     */
    protected function getWorkflowNamesForExclusion($workflowName)
    {
        $workflow = $this->workflowRegistry->getWorkflow($workflowName);
        if ($workflow) {
            $activeWorkflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
                $workflow->getDefinition()->getExclusiveActiveGroups()
            );

            $workflows = array_map(
                function (Workflow $workflow) {
                    return $workflow->getName();
                },
                $activeWorkflows
            );
        }

        $workflows[] = $workflowName;

        return array_unique($workflows);
    }
}
