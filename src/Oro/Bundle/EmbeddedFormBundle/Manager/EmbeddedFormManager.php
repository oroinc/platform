<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;

class EmbeddedFormManager
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var array
     */
    protected $formTypes = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, FormFactoryInterface $formFactory)
    {
        $this->container = $container;
        $this->formFactory = $formFactory;
    }

    /**
     * @param $type
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createForm($type, $data = null, $options = [])
    {
        $options = array_replace($options, ['channel_form_type' => 'oro_entity_identifier']);

        if ($this->container->has($type)) {
            return $this->formFactory->create($this->container->get($type), $data, $options);
        }

        if (class_exists($type)) {
            return $this->formFactory->create(new $type, $data, $options);
        }

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
}
