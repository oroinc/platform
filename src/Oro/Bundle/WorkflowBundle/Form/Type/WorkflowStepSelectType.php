<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowStepSelectType extends AbstractType
{
    const NAME = 'oro_workflow_step_select';

    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var MessageCatalogueInterface */
    private $translatorCatalogue;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(WorkflowRegistry $workflowRegistry, TranslatorInterface $translator)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['workflow_entity_class', 'workflow_name']);
        $resolver->setDefaults(
            [
                'class' => 'OroWorkflowBundle:WorkflowStep',
                'choice_label' => 'label'
            ]
        );

        $resolver->setNormalizer(
            'query_builder',
            function (Options $options, $qb) {
                if (!$qb) {
                    $qb = $this->getQueryBuilder(
                        $options['em'],
                        $options['class'],
                        array_map(
                            function (Workflow $workflow) {
                                return $workflow->getDefinition();
                            },
                            $this->getWorkflows($options)
                        )
                    );
                }

                return $qb;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $workflowsCount = count($this->getWorkflows($options));

        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            if ($workflowsCount > 1) {
                /** @var WorkflowStep $step */
                $step = $choiceView->data;
                $choiceView->label = sprintf(
                    '%s: %s',
                    $this->getTranslation($step->getDefinition()->getLabel()),
                    $this->getTranslation($choiceView->label)
                );
            } else {
                $choiceView->label = $this->getTranslation($choiceView->label);
            }
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function getTranslation($value)
    {
        if ($this->hasTranslation($value, WorkflowTranslationHelper::TRANSLATION_DOMAIN)) {
            $value = $this->translator->trans($value, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        }

        return $value;
    }

    /**
     * @param string $value
     * @param string $domain
     * @return bool
     */
    private function hasTranslation($value, $domain)
    {
        if ($this->translator instanceof TranslatorBagInterface) {
            if (!$this->translatorCatalogue) {
                $this->translatorCatalogue = $this->translator->getCatalogue();
            }

            return $this->translatorCatalogue->has($value, $domain);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }

    /**
     * @param EntityManager $em
     * @param string $className
     * @param array $definitions
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder(EntityManager $em, $className, array $definitions)
    {
        $qb = $em->getRepository($className)->createQueryBuilder('ws');

        return $qb->where($qb->expr()->in('ws.definition', ':workflowDefinitions'))
            ->setParameter('workflowDefinitions', $definitions)
            ->orderBy('ws.definition', 'ASC')
            ->orderBy('ws.stepOrder', 'ASC')
            ->orderBy('ws.label', 'ASC');
    }

    /**
     * @param array|Options $options
     *
     * @return array
     */
    protected function getWorkflows($options)
    {
        if (isset($options['workflow_name'])) {
            $workflowName = $options['workflow_name'];
            $workflows = [$this->workflowRegistry->getWorkflow($workflowName)];
        } elseif (isset($options['workflow_entity_class'])) {
            $workflows = $this->workflowRegistry->getActiveWorkflowsByEntityClass($options['workflow_entity_class'])
                ->getValues();
        } else {
            throw new \InvalidArgumentException('Either "workflow_name" or "workflow_entity_class" must be set');
        }

        return $workflows;
    }
}
