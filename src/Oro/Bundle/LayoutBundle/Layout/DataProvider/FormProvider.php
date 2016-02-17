<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\AbstractDataProvider;
use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use Oro\Bundle\FormBundle\Form\Handler\FormProviderInterface;

class FormProvider extends AbstractDataProvider implements FormProviderInterface
{
    /**
     * @var FormAccessor
     */
    protected $formAccessor;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var string
     */
    protected $formType;

    /**
     * @var string
     */
    protected $actionRoute;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @param string $actionRoute
     */
    public function setActionRoute($actionRoute)
    {
        $this->actionRoute = $actionRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->formAccessor) {
            $action = $this->actionRoute ? FormAction::createByRoute($this->actionRoute) : null;
            $options = $this->buildFormOptionsByContext($context);
            $this->formAccessor = new FormAccessor(
                $this->getForm(null, $options),
                $action
            );
        }

        return $this->formAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getForm($data = null, array $options = [])
    {
        if (!$this->formType) {
            throw new \RuntimeException(sprintf('%s::formType should be defined', __CLASS__));
        }
        if (!$this->form) {
            $this->form = $this->formFactory->create($this->formType, $data, $options);
        }

        return $this->form;
    }

    /**
     * Use $options['data'] for setting up form data.
     *
     * @param ContextInterface $context
     * @return array
     */
    protected function buildFormOptionsByContext(ContextInterface $context)
    {
        return [];
    }
}
