<?php

namespace Oro\Bundle\ActivityBundle\Form\Extension;

use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * The form extension that adds "contexts" field to forms for activity entities.
 * This field is used to associate the activity entity with other entities
 * that participates in the activity or have a relation to it.
 *
 * Example: if there is an email thread (an activity) where an user is having conversation with a customer (Account)
 * about a deal (an Opportunity) - both these records will make sense as contexts for the email thread.
 */
class ContextsExtension extends AbstractTypeExtension implements ServiceSubscriberInterface
{
    use FormExtendedTypeTrait;

    /** @var RequestStack */
    protected $requestStack;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ContainerInterface */
    protected $container;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack,
        AuthorizationCheckerInterface $authorizationChecker,
        ContainerInterface $container
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
        $this->authorizationChecker = $authorizationChecker;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_activity.manager' => ActivityManager::class,
            'oro_entity.entity_alias_resolver' => EntityAliasResolver::class,
            'oro_entity.routing_helper' => EntityRoutingHelper::class
        ];
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
        $alias = $this->getEntityAliasResolver()->getPluralAlias($className);

        $defaultOptions = [
            'label'          => 'oro.activity.contexts.label',
            'tooltip'        => 'oro.activity.contexts.tooltip',
            'required'       => false,
            'mapped'         => false,
            'error_bubbling' => false,
            'configs'   => [
                'route_name'       => 'oro_activity_form_autocomplete_search',
                'route_parameters' => [
                    'activity' => $alias,
                    'name'     => $alias
                ],
            ]
        ];

        $builder->add(
            'contexts',
            ContextsSelectType::class,
            array_merge($defaultOptions, $options['contexts_options'])
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this, 'addDefaultContextListener']
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'setActivityTargetsContext']
        );
    }

    /**
     * Adds default or existent activity contexts data to the form
     */
    public function addDefaultContextListener(FormEvent $event)
    {
        /** @var ActivityInterface $entity */
        $entity = $event->getData();
        $form   = $event->getForm();

        if ($entity) {
            $entityRoutingHelper = $this->getEntityRoutingHelper();
            $request = $this->requestStack->getCurrentRequest();
            $targetEntityClass = $entityRoutingHelper->getEntityClassName($request);
            $targetEntityId = $entityRoutingHelper->getEntityId($request);
            $contexts = [];

            if ($entity->getId()) {
                $contexts = $entity->getActivityTargets();
            } elseif ($targetEntityClass && $request->getMethod() === 'GET') {
                $contexts[] = $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
            }

            $form->get('contexts')->setData($this->getAccessibleContexts($contexts));
        }
    }

    /**
     * Set activity targets with context data to the form
     */
    public function setActivityTargetsContext(FormEvent $event)
    {
        /** @var ActivityInterface $entity */
        $entity = $event->getData();
        $form   = $event->getForm();

        if ($entity && $form->isSubmitted() && $form->isValid() && $form->has('contexts')) {
            $contexts = $this->getAccessibleContexts($form->get('contexts')->getData());
            $inaccessibleContexts = $this->getInaccessibleContexts($entity->getActivityTargets());
            $this->getActivityManager()->setActivityTargets($entity, array_merge($contexts, $inaccessibleContexts));
        }
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
        $resolver->setDefaults(
            [
                'contexts_disabled' => false,
                'contexts_options' => []
            ]
        );
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

        $activities = $this->getActivityManager()->getActivityTypes();

        return in_array($className, $activities, true);
    }

    /**
     * @param iterable|object[] $contexts
     * @return array
     */
    protected function getAccessibleContexts($contexts): array
    {
        $result = [];
        foreach ($contexts as $context) {
            if ($this->authorizationChecker->isGranted('VIEW', $context)) {
                $result[] = $context;
            }
        }
        return $result;
    }

    /**
     * @param iterable|object[] $contexts
     * @return array
     */
    protected function getInaccessibleContexts($contexts): array
    {
        $result = [];
        foreach ($contexts as $context) {
            if (!$this->authorizationChecker->isGranted('VIEW', $context)) {
                $result[] = $context;
            }
        }
        return $result;
    }

    protected function getActivityManager(): ActivityManager
    {
        return $this->container->get('oro_activity.manager');
    }

    protected function getEntityAliasResolver(): EntityAliasResolver
    {
        return $this->container->get('oro_entity.entity_alias_resolver');
    }

    protected function getEntityRoutingHelper(): EntityRoutingHelper
    {
        return $this->container->get('oro_entity.routing_helper');
    }
}
