<?php

namespace Oro\Bundle\WorkflowBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Utils\FormUtils;

class RestrictionsExtension extends AbstractTypeExtension
{
    /**
     * @var WorkflowManager
     */
    protected $workflowManager;
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WorkflowRestrictionRepository
     */
    protected $restrictionRepository;

    /**
     * @var array [$entityClass => [$workflowName => $workflowData, ...], ...]
     */
    protected $workflows;

    /**
     * @param WorkflowManager $workflowManager
     * @param DoctrineHelper  $doctrineHelper
     */
    public function __construct(WorkflowManager $workflowManager, DoctrineHelper $doctrineHelper)
    {
        $this->workflowManager = $workflowManager;
        $this->doctrineHelper  = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['disable_workflow_restrictions'] || empty($options['data_class'])) {
            return;
        }
        $entityClass  = $options['data_class'];
        $restrictions = $this->getRestrictionRepository()->getRestrictions($entityClass);
        /** @var WorkflowRestriction[] $applicableRestrictions */
        $applicableRestrictions = [];
        foreach ($restrictions as $restriction) {
            $workflowDefinition = $restriction->getDefinition();
            $workflowName       = $workflowDefinition->getName();
            if (!isset($this->workflows[$entityClass][$workflowName])) {
                $workflow = $this->workflowManager
                    ->getApplicableWorkflowByEntityClass(
                        $workflowDefinition->getRelatedEntity()
                    );

                $this->workflows[$entityClass][$workflowName] = [
                    'is_active' => null !== $workflow,
                    'workflow'  => $workflow
                ];
            }
            if ($this->workflows[$entityClass][$workflowName]['is_active']) {
                $applicableRestrictions[] = $restriction;
            }
        }
        if (!empty($applicableRestrictions)) {
            $builder->addEventListener(
                FormEvents::POST_SET_DATA,
                function (FormEvent $event) use ($applicableRestrictions, $entityClass) {
                    $data = $event->getData();
                    $form = $event->getForm();
                    if (!$data) {
                        return;
                    }
                    $isNew = $this->doctrineHelper->isNewEntity($data);
                    foreach ($applicableRestrictions as $restriction) {
                        $found = false;
                        $step = $restriction->getStep();
                        if (!$step && !$isNew) {
                            continue;
                        }
                        if (!$step) {
                            $found = true;
                        } elseif (!$isNew) {
                            $workflowEntity         = $restriction->getDefinition()->getRelatedEntity();
                            $restrictionEntityClass = $restriction->getEntityClass();
                            if ($workflowEntity === $restrictionEntityClass) {
                                $workflowItem = $this->workflowManager->getWorkflowItemByEntity($data);
                                if ($workflowItem) {
                                    $found = $workflowItem->getCurrentStep() === $step;
                                }
                            } else {
                                $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($workflowEntity);
                                $targets       = $classMetadata->getAssociationsByTargetClass($restrictionEntityClass);
                                $target        = reset($targets);
                                if ($target) {
                                    $id                     = $this->doctrineHelper->getSingleEntityIdentifier($data);
                                    $targetFieldName        = $target['fieldName'];
                                    $workflowEntityIdField  = $this->doctrineHelper
                                        ->getSingleEntityIdentifierFieldName($workflowEntity);
                                    $workflowItemRepository = $this->doctrineHelper
                                        ->getEntityRepositoryForClass('OroWorkflowBundle:WorkflowItem');

                                    $found = $workflowItemRepository
                                        ->createQueryBuilder('wi')
                                        ->innerJoin(
                                            $workflowEntity,
                                            'we',
                                            Join::WITH,
                                            sprintf('we.%s = wi.entityId', $workflowEntityIdField)
                                        )
                                        ->where(sprintf('we.%s = :id', $targetFieldName))
                                        ->andWhere('wi.currentStep = :step')
                                        ->setParameters(['id' => $id, 'step' => $step])
                                        ->setMaxResults(1)
                                        ->getQuery()
                                        ->getOneOrNullResult();
                                }
                            }
                        }

                        if (!empty($found)) {
                            $this->applyRestriction($restriction, $form);
                        }
                    }
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['disable_workflow_restrictions' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * @return WorkflowRestrictionRepository
     */
    protected function getRestrictionRepository()
    {
        if (null === $this->restrictionRepository) {
            $this->restrictionRepository = $this->doctrineHelper
                ->getEntityRepositoryForClass('OroWorkflowBundle:WorkflowRestriction');
        }

        return $this->restrictionRepository;
    }

    /**
     * @param WorkflowRestriction $restriction
     * @param FormInterface       $form
     *
     */
    protected function applyRestriction(WorkflowRestriction $restriction, FormInterface $form)
    {
        $field = $restriction->getField();
        if ($restriction->getMode() === 'full') {
            FormUtils::replaceField($form, $field, ['disabled' => true]);
        } elseif ($restriction->getValues()) {
            if ($restriction->getMode() === 'disallow') {
                $this->tryDisableFieldValues($form, $field, $restriction->getValues());
            } elseif ($restriction->getMode() === 'allow') {
                $restrictionClosure = function ($value) use ($restriction) {
                    return in_array($value, $restriction->getValues());
                };
                $this->tryDisableFieldValues($form, $field, $restrictionClosure);
            }
        }
    }

    /**
     * @param FormInterface  $form
     * @param string         $field
     * @param array|callable $disabledValues
     */
    protected function tryDisableFieldValues(FormInterface $form, $field, $disabledValues)
    {
        $fieldForm = $form->get($field);
        if ($fieldForm->getConfig()->hasOption('disabled_values')) {
            FormUtils::replaceField($form, $field, ['disabled_values' => $disabledValues]);
        }
    }
}
