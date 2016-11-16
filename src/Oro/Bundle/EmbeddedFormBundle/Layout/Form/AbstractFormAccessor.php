<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Symfony\Component\Form\FormView;

abstract class AbstractFormAccessor implements FormAccessorInterface
{
    /** @var FormAction */
    protected $action;

    /** @var string|null */
    protected $method;

    /** @var string|null */
    protected $enctype;

    /** @var FormView */
    private $formView;

    /** @var array */
    private $processedFields;

    /** @var bool */
    private $paramsInitialized = false;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getForm()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getView()->vars['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAction()
    {
        $this->ensureParamsInitialized();

        return $this->action;
    }

    /**
     * @param FormAction $action
     */
    public function setAction(FormAction $action)
    {
        $this->action = $action;
    }

    /**
     * @param string $route
     * @param array $routeParams
     */
    public function setActionRoute($route, array $routeParams = [])
    {
        $this->action = FormAction::createByRoute($route, $routeParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        $this->ensureParamsInitialized();

        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnctype()
    {
        $this->ensureParamsInitialized();

        return $this->enctype;
    }

    /**
     * @param string|null $enctype
     */
    public function setEnctype($enctype)
    {
        $this->enctype = $enctype;
    }

    /**
     * {@inheritdoc}
     */
    public function getView($fieldPath = null)
    {
        $result = $this->getFormView();
        if ($fieldPath !== null) {
            foreach (explode('.', $fieldPath) as $field) {
                $result = $result[$field];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessedFields()
    {
        return $this->processedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessedFields($processedFields)
    {
        $this->processedFields = $processedFields;
    }

    /**
     * @return FormView
     */
    protected function getFormView()
    {
        if (!$this->formView) {
            $this->formView = $this->getForm()->createView();
        }

        return $this->formView;
    }

    /**
     * Builds a string representation of the form accessor.
     * This string is used as a part of the key for the layout cache.
     *
     * @param string          $prefix  A string that can be used as the form identifier
     * @param FormAction|null $action  The submit action of the form
     * @param string|null     $method  The submit method of the form
     * @param string|null     $enctype The encryption type of the form
     *
     * @return string
     */
    protected function buildHash($prefix, FormAction $action = null, $method = null, $enctype = null)
    {
        $result = $prefix;
        if (null !== $action && !$action->isEmpty()) {
            $result .= ';action_' . $action->toString();
        }
        if (!empty($method)) {
            $result .= ';method:' . $method;
        }
        if (!empty($enctype)) {
            $result .= ';enctype:' . $enctype;
        }

        return $result;
    }

    /**
     * Makes sure that the action, method and enctype are initialized.
     */
    protected function ensureParamsInitialized()
    {
        if ($this->paramsInitialized) {
            return;
        }

        if (null === $this->action) {
            $action       = $this->getForm()->getConfig()->getAction();
            $this->action = $action
                ? FormAction::createByPath($action)
                : FormAction::createEmpty();
        }
        if (null === $this->method) {
            $this->method = $this->getForm()->getConfig()->getMethod();
        }
        if (null !== $this->method) {
            $this->method = strtoupper($this->method);
        }
        if (null === $this->enctype && $this->getFormView()->vars['multipart']) {
            $this->enctype = 'multipart/form-data';
        }

        $this->paramsInitialized = true;
    }
}
