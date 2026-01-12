<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Symfony\Component\Form\FormView;

/**
 * Provides common functionality for accessing and managing forms in the layout system.
 *
 * This base class implements form accessor methods for retrieving form views, processing fields,
 * and managing form parameters like action, method, and encoding type. Subclasses must implement
 * the `getForm` method to provide the actual form instance.
 */
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

    #[\Override]
    public function getName()
    {
        return $this->getForm()->getName();
    }

    #[\Override]
    public function getId()
    {
        return $this->getView()->vars['id'];
    }

    #[\Override]
    public function getAction()
    {
        $this->ensureParamsInitialized();

        return $this->action;
    }

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

    #[\Override]
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

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function getProcessedFields()
    {
        return $this->processedFields;
    }

    #[\Override]
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
    protected function buildHash($prefix, ?FormAction $action = null, $method = null, $enctype = null)
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
