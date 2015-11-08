<?php

namespace Oro\Bundle\ActivityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class ContextsExtension extends AbstractTypeExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param ActivityManager     $activityManager
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActivityManager $activityManager,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->doctrineHelper      = $doctrineHelper;
        $this->activityManager     = $activityManager;
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $className = $options['data_class'];
        $alias     = $this->entityAliasResolver->getPluralAlias($className);

        $builder->add(
            'contexts',
            'oro_activity_contexts_select',
            [
                'label'     => 'oro.activity.contexts.label',
                'tooltip'   => 'oro.activity.contexts.tooltip',
                'required'  => false,
                'read_only' => false,
                'mapped'    => false,
                'configs'   => [
                    'route_name'       => 'oro_activity_form_autocomplete_search',
                    'route_parameters' => [
                        'activity' => $alias,
                        'name'     => $alias
                    ],
                ]
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var ActivityInterface $entity */
            $entity = $event->getData();
            $form   = $event->getForm();

            if ($entity) {
                $contexts = $entity->getActivityTargetEntities();
                $form->get('contexts')->setData($contexts);
            }
        });
    }

    /**
     * {@inheritdoc}
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
        $resolver->setDefaults(['contexts_disabled' => false]);
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

        $activities = $this->activityManager->getActivityTypes();

        return in_array($className, $activities, true);
    }
}
