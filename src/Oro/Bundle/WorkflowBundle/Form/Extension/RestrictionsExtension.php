<?php

namespace Oro\Bundle\WorkflowBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

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
     * @var RestrictionManager
     */
    protected $restrictionsManager;

    /**
     * @param WorkflowManager    $workflowManager
     * @param DoctrineHelper     $doctrineHelper
     * @param RestrictionManager $restrictionManager
     */
    public function __construct(
        WorkflowManager $workflowManager,
        DoctrineHelper $doctrineHelper,
        RestrictionManager $restrictionManager
    ) {
        $this->workflowManager     = $workflowManager;
        $this->doctrineHelper      = $doctrineHelper;
        $this->restrictionsManager = $restrictionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['disable_workflow_restrictions'] ||
            empty($options['data_class']) ||
            !$this->restrictionsManager->hasEntityClassRestrictions($options['data_class'])
        ) {
            return;
        }
        $data = $form->getData();
        if (!$data) {
            return;
        }
        $restrictions = $this->restrictionsManager->getEntityRestrictions($data);
        foreach ($restrictions as $restriction) {
            if ($form->has($restriction['field'])) {
                $this->applyRestriction($restriction, $view);
            }
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
     * @param array    $restriction
     * @param FormView $view
     */
    protected function applyRestriction(array $restriction, FormView $view)
    {
        $field = $restriction['field'];
        $mode  = $restriction['mode'];
        if ($mode === 'full') {
            $view->children[$field]->vars['attrs']['disabled'] = true;
        } else {
            $values = $restriction['values'];
            if ($mode === 'disallow') {
                $view->children[$field]->vars['choices'] = array_filter(
                    $view->vars['form']->children[$field]->vars['choices'],
                    function (ChoiceView $choice) use ($values) {
                        return !in_array($choice->value, $values);
                    }
                );
            } elseif ($mode === 'allow') {
                $view->children[$field]->vars['choices'] = array_filter(
                    $view->vars['form']->children[$field]->vars['choices'],
                    function (ChoiceView $choice) use ($values) {
                        return in_array($choice->value, $values);
                    }
                );
            }
        }
    }
}
