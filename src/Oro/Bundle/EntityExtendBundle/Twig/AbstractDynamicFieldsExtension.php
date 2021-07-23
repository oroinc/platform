<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Abstract Twig extension for the implementations of the functions to get dynamic fields of entities:
 *   - oro_get_dynamic_fields
 *   - oro_get_dynamic_field
 */
abstract class AbstractDynamicFieldsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

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
            new TwigFunction('oro_get_dynamic_fields', [$this, 'getFields']),
            new TwigFunction('oro_get_dynamic_field', [$this, 'getField']),
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
}
