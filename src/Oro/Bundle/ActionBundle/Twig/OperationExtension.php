<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class OperationExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /** @var OperationManager */
    protected $manager;

    /** @var ApplicationsHelper */
    protected $appsHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var  ContextHelper */
    protected $contextHelper;

    /**
     * @param OperationManager $manager
     * @param ApplicationsHelper $appsHelper
     * @param DoctrineHelper $doctrineHelper
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        OperationManager $manager,
        ApplicationsHelper $appsHelper,
        DoctrineHelper $doctrineHelper,
        ContextHelper $contextHelper
    ) {
        $this->manager = $manager;
        $this->appsHelper = $appsHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->contextHelper = $contextHelper;
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
                [$this->contextHelper, 'getActionParameters'],
                ['needs_context' => true]
            ),
            new \Twig_SimpleFunction('oro_action_widget_route', [$this, 'getWidgetRoute']),
            new \Twig_SimpleFunction('has_actions', [$this, 'hasActions']),
        );
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
        return $this->manager->hasOperations($params);
    }
}
