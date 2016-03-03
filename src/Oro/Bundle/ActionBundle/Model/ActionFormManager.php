<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;

class ActionFormManager
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var ActionManager */
    protected $actionManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ActionManager $actionManager,
        ContextHelper $contextHelper
    ) {
        $this->formFactory = $formFactory;
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
    }

    /**
     * @param string $actionName
     * @param ActionData $data
     * @return Form
     */
    public function getActionForm($actionName, ActionData $data)
    {
        $operation = $this->actionManager->getAction($actionName, $data);

        return $this->formFactory->create(
            $operation->getDefinition()->getFormType(),
            $data,
            array_merge($operation->getFormOptions($data), ['action' => $operation])
        );
    }
}
