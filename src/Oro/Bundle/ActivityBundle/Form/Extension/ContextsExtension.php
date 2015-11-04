<?php

namespace Oro\Bundle\ActivityBundle\Form\Extension;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextsExtension extends AbstractTypeExtension
{
    const ACTIVITY_INTERFACE = 'Oro\Bundle\ActivityBundle\Model\ActivityInterface';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper      $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $builder->add(
            'contexts',
            'oro_activity_contexts_select',
            [
                'label'     => 'oro.activity.contexts.label',
                'tooltip'   => 'oro.activity.contexts.tooltip',
                'required'  => false,
                'read_only' => false,
                'mapped'    => false,
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var ActivityInterface $entity */
            $entity = $event->getData();
            $form = $event->getForm();

            if ($entity) {
                $contexts = $entity->getActivityTargetEntities();
                $form->get('contexts')->setData($contexts);
            }
        });
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $view->children['contexts']->vars['extra_field'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'contexts_disabled' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function isApplicable(array $options)
    {
        if ($options['contexts_disabled'] || empty($options['data_class'])) {
            return false;
        }

        $className = $options['data_class'];
        if (!$this->doctrineHelper->isManageableEntity($className)) {
            return false;
        }

        $refClass = new \ReflectionClass($options['data_class']);
        if (!$refClass->implementsInterface(self::ACTIVITY_INTERFACE)) {
            return false;
        }

        return true;
    }
}
