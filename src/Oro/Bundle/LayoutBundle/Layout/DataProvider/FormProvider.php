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
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->formAccessor) {
            $this->formAccessor = $this->createFormAccessor($context);
        }

        return $this->formAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getForm($data = null, array $options = [])
    {
        if (!$this->form) {
            $this->form = $this->createForm($data, $options);
        }

        return $this->form;
    }

    /**
     *
     * @param ContextInterface $context
     * @return FormAccessor
     */
    protected function createFormAccessor(ContextInterface $context)
    {
        $this->buildFormOptionsByContext($context);
        $action = $this->actionRoute ? FormAction::createByRoute($this->actionRoute) : null;
        return new FormAccessor(
            $this->getForm(null, []),
            $action
        );
    }

    /**
     * @param ContextInterface $context
     * @return array
     */
    protected function buildFormOptionsByContext(ContextInterface $context)
    {
        return [];
    }

    /**
     * @param mixed $data
     * @param array $options
     * @return FormInterface
     */
    protected function createForm($data = null, array $options = [])
    {
        if (!$this->formType) {
            throw new \RuntimeException(sprintf('%s::formType should be defined', __CLASS__));
        }
        return $this->formFactory->create($this->formType, $data, $options);
    }
}
