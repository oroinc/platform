<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface as FormDataTransformerInterface;

class EntityDataTransformer implements DataTransformerInterface
{
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
    public function transform($class, $property, $value, $config)
    {
        if (isset($config['data_transformer'])) {
            foreach ((array)$config['data_transformer'] as $transformerServiceId) {
                $transformer = $this->container->get($transformerServiceId);
                $value       = $this->transformByCustomTransformer($transformer, $class, $property, $value, $config);
            }
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string)$value;
            } elseif ($value instanceof \DateTime) {
                $value = $value->format('c');
            }
        }

        return $value;
    }

    /**
     * @param object $transformer
     * @param string $class
     * @param string $property
     * @param mixed  $value
     * @param array  $config
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if the given data transformer has unknown type
     */
    protected function transformByCustomTransformer($transformer, $class, $property, $value, $config)
    {
        if ($transformer instanceof DataTransformerInterface) {
            return $transformer->transform($class, $property, $value, $config);
        }
        if ($transformer instanceof FormDataTransformerInterface) {
            return $transformer->transform($value);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Not supported data transformer type: %s',
                is_object($transformer) ? get_class($transformer) : gettype($transformer)
            )
        );
    }
}
