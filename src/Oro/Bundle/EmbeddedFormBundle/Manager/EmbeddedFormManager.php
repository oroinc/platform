<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormInterface;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormTypeInterface;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;

/**
 * Handles logic for creating and manipulation with embedded forms
 */
class EmbeddedFormManager
{
    /** @var FormRegistryInterface */
    protected $formRegistry;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var array */
    protected $formTypes = [];

    /**
     * @param FormRegistryInterface $formRegistry
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormRegistryInterface $formRegistry, FormFactoryInterface $formFactory)
    {
        $this->formRegistry   = $formRegistry;
        $this->formFactory = $formFactory;
    }

    /**
     * @param       $type
     * @param null  $data
     * @param array $options
     *
     * @return FormInterface
     */
    public function createForm($type, $data = null, $options = [])
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * @param string $label
     * @param string $type
     */
    public function addFormType($type, $label = null)
    {
        $this->formTypes[$type] = $label ? : $type;
    }

    /**
     * @param string $type
     *
     * @return string|null
     */
    public function getLabelByType($type)
    {
        return isset($this->formTypes[$type]) ? $this->formTypes[$type] : null;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->formTypes;
    }

    /**
     * @return array
     */
    public function getAllChoices()
    {
        return array_flip($this->formTypes);
    }

    /**
     * @param string $type
     *
     * @return string|null
     */
    public function get($type)
    {
        if (isset($this->formTypes[$type])) {
            return $this->formTypes[$type];
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getDefaultCssByType($type)
    {
        $typeInstance = $this->getTypeInstance($type);

        if ($typeInstance instanceof EmbeddedFormInterface) {
            return $typeInstance->getDefaultCss();
        }

        return '';
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getDefaultSuccessMessageByType($type)
    {
        $typeInstance = $this->getTypeInstance($type);

        if ($typeInstance instanceof EmbeddedFormInterface) {
            return $typeInstance->getDefaultSuccessMessage();
        }

        return '';
    }

    /**
     * @param string $type
     *
     * @return string
     *
     * @deprecated since 1.7. Please implement LayoutUpdateInterface in your form type instead.
     */
    public function getCustomFormLayoutByFormType($type)
    {
        $typeInstance = $this->getTypeInstance($type);

        if ($typeInstance instanceof CustomLayoutFormTypeInterface) {
            return $typeInstance->geFormLayout();
        }

        if ($typeInstance instanceof CustomLayoutFormInterface) {
            return $typeInstance->getFormLayout();
        }

        return '';
    }

    /**
     * Gets FormType instance by its name from  Form Registry
     * if name is passed
     * @param string|null $type
     *
     * @return EmbeddedFormInterface|AbstractType|null
     */
    public function getTypeInstance($type)
    {
        return ($type ? $this->formRegistry->getType($type) : $type);
    }
}
