<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormInterface;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\CustomLayoutFormTypeInterface;

class EmbeddedFormManager
{
    /** @var ContainerInterface */
    protected $container;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var array */
    protected $formTypes = [];

    /**
     * @param ContainerInterface   $container
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(ContainerInterface $container, FormFactoryInterface $formFactory)
    {
        $this->container   = $container;
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
        $type    = $this->getTypeInstance($type) ? : $type;

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
     * @param string $type
     *
     * @return EmbeddedFormInterface|AbstractType
     */
    public function getTypeInstance($type)
    {
        $typeInstance = null;
        if ($this->container->has($type)) {
            $typeInstance = $this->container->get($type);
            return $typeInstance;
        } elseif (class_exists($type)) {
            $typeInstance = new $type();
            return $typeInstance;
        }
        return $typeInstance;
    }
}
