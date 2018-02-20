<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDynamicFieldsExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config_fields';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_dynamic_fields', [$this, 'getFields']),
            new \Twig_SimpleFunction('oro_get_dynamic_field', [$this, 'getField']),
        ];
    }

    /**
     * @param object      $entity
     * @param null|string $entityClass
     * @return array
     */
    abstract public function getFields($entity, $entityClass = null);

    /**
     * @param object $entity
     * @param FieldConfigModel $field
     * @return array
     */
    abstract public function getField($entity, FieldConfigModel $field);

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
