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
        if (isset($config[ConfigUtil::DATA_TRANSFORMER])) {
            foreach ((array)$config[ConfigUtil::DATA_TRANSFORMER] as $transformerServiceId) {
                $transformer = $this->container->get($transformerServiceId);
                $value       = $this->transformByCustomTransformer($transformer, $class, $property, $value, $config);
            }
        }

        return null !== $this->baseDataTransformer
            ? $this->baseDataTransformer->transform($class, $property, $value, $config)
            : $value;
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
