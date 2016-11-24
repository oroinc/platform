<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;

class OperationExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /** @var ApplicationsHelper */
    protected $appsHelper;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var OptionsHelper */
    protected $optionsHelper;

    /** @var ButtonProvider */
    protected $buttonProvider;

    /** @var ButtonSearchContextProvider */
    protected $searchContextProvider;

    /**
     * @param ApplicationsHelperInterface $appsHelper
     * @param ContextHelper $contextHelper
     * @param OptionsHelper $optionsHelper
     * @param ButtonProvider $buttonProvider
     * @param ButtonSearchContextProvider $searchContextProvider
     */
    public function __construct(
        ApplicationsHelperInterface $appsHelper,
        ContextHelper $contextHelper,
        OptionsHelper $optionsHelper,
        ButtonProvider $buttonProvider,
        ButtonSearchContextProvider $searchContextProvider
    ) {
        $this->appsHelper = $appsHelper;
        $this->contextHelper = $contextHelper;
        $this->optionsHelper = $optionsHelper;
        $this->buttonProvider = $buttonProvider;
        $this->searchContextProvider = $searchContextProvider;
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
        return [
            new \Twig_SimpleFunction(
                'oro_action_widget_parameters',
                [$this->contextHelper, 'getActionParameters'],
                ['needs_context' => true]
            ),
            new \Twig_SimpleFunction('oro_action_widget_route', [$this->appsHelper, 'getWidgetRoute']),
            new \Twig_SimpleFunction('oro_action_frontend_options', [$this->optionsHelper, 'getFrontendOptions']),
            new \Twig_SimpleFunction('oro_action_has_buttons', [$this, 'hasButtons']),
        ];
    }

    /**
     * @param array $context
     *
     * @return bool
     */
    public function hasButtons(array $context)
    {
        return $this->buttonProvider->hasButtons(
            $this->searchContextProvider->getButtonSearchContext($this->contextHelper->getContext($context))
        );
    }
}
