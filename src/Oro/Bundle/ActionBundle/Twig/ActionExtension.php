<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /** @var ActionManager */
    protected $manager;

    /** @var ApplicationsHelper */
    protected $appsHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param ActionManager $manager
     * @param ApplicationsHelper $appsHelper
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        ActionManager $manager,
        ApplicationsHelper $appsHelper,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->manager = $manager;
        $this->appsHelper = $appsHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'oro_action_widget_parameters',
                [$this, 'getWidgetParameters'],
                ['needs_context' => true]
            ),
            new \Twig_SimpleFunction('oro_action_widget_route', [$this, 'getWidgetRoute']),
            new \Twig_SimpleFunction('has_actions', [$this, 'hasActions']),
        );
    }

    /**
     * @param array $context
     * @return array
     */
    public function getWidgetParameters(array $context)
    {
        $request = $this->requestStack->getMasterRequest();

        $params = [
            'route' => $request->get('_route'),
            'fromUrl' => $request->getRequestUri()
        ];

        if (array_key_exists('entity', $context) && is_object($context['entity']) &&
            !$this->doctrineHelper->isNewEntity($context['entity'])
        ) {
            $params['entityId'] = $this->doctrineHelper->getEntityIdentifier($context['entity']);
            $params['entityClass'] = get_class($context['entity']);
        } elseif (isset($context['entity_class'])) {
            $params['entityClass'] = $context['entity_class'];
        }

        return $params;
    }

    /**
     * @return string
     */
    public function getWidgetRoute()
    {
        return $this->appsHelper->getWidgetRoute();
    }

    /**
     * @param array $params
     * @return bool
     */
    public function hasActions(array $params)
    {
        return $this->manager->hasActions($params);
    }
}
