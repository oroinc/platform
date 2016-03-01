<?php

namespace Oro\Component\EntitySerializer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface as FormDataTransformerInterface;

class EntityDataTransformer implements DataTransformerInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var DataTransformerInterface */
    protected $baseDataTransformer;

    /**
     * @param ContainerInterface            $container
     * @param DataTransformerInterface|null $baseDataTransformer
     */
    public function __construct(ContainerInterface $container, DataTransformerInterface $baseDataTransformer = null)
    {
        $this->container           = $container;
        $this->baseDataTransformer = $baseDataTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($class, $property, $value, $config)
    {
        if (isset($config[FieldConfig::DATA_TRANSFORMER])) {
            foreach ($config[FieldConfig::DATA_TRANSFORMER] as $transformer) {
                $value = $this->transformByCustomTransformer($transformer, $class, $property, $value, $config);
            }
        }

        return null !== $this->baseDataTransformer
            ? $this->baseDataTransformer->transform($class, $property, $value, $config)
            : $value;
    }

    /**
     * @param mixed  $transformer
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
        if (is_string($transformer)) {
            $transformerService = $this->container->get($transformer, ContainerInterface::NULL_ON_INVALID_REFERENCE);
            if (null === $transformerService) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Undefined data transformer service "%s". Class: %s. Property: %s.',
                        $transformer,
                        $class,
                        $property
                    )
                );
            }
            $transformer = $transformerService;
        }

        if ($transformer instanceof DataTransformerInterface) {
            return $transformer->transform($class, $property, $value, $config);
        }
        if ($transformer instanceof FormDataTransformerInterface) {
            return $transformer->transform($value);
        }
        if (is_callable($transformer)) {
            return call_user_func($transformer, $class, $property, $value, $config);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Unexpected type of data transformer "%s". Expected "%s", "%s" or "%s". Class: %s. Property: %s.',
                is_object($transformer) ? get_class($transformer) : gettype($transformer),
                'Oro\Component\EntitySerializer\DataTransformerInterface',
                'Symfony\Component\Form\DataTransformerInterface',
                'callable',
                $class,
                $property
            )
        );
    }
}
