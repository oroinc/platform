<?php

namespace Oro\Component\EntitySerializer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface as FormDataTransformerInterface;

/**
 * The implementation of the data transformer that executes transformer(s)
 * from "data_transformer" configuration attribute.
 */
class DataTransformer implements DataTransformerInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value, array $config, array $context)
    {
        if (isset($config[ConfigUtil::DATA_TRANSFORMER])) {
            foreach ($config[ConfigUtil::DATA_TRANSFORMER] as $transformer) {
                $value = $this->transformByCustomTransformer(
                    $transformer,
                    $value,
                    $config,
                    $context
                );
            }
        }

        return $value;
    }

    /**
     * @param mixed  $transformer
     * @param mixed  $value
     * @param array  $config
     * @param array  $context
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if the given data transformer has unknown type
     */
    protected function transformByCustomTransformer(
        $transformer,
        $value,
        array $config,
        array $context
    ) {
        if (\is_string($transformer)) {
            $transformerService = $this->container->get($transformer, ContainerInterface::NULL_ON_INVALID_REFERENCE);
            if (null === $transformerService) {
                throw new \InvalidArgumentException(sprintf(
                    'Undefined data transformer service "%s".',
                    $transformer
                ));
            }
            $transformer = $transformerService;
        }

        if ($transformer instanceof DataTransformerInterface) {
            return $transformer->transform($value, $config, $context);
        }
        if ($transformer instanceof FormDataTransformerInterface) {
            return $transformer->transform($value);
        }
        if (\is_callable($transformer)) {
            return $transformer($value, $config, $context);
        }

        throw new \InvalidArgumentException(sprintf(
            'Unexpected type of data transformer "%s". Expected "%s", "%s" or "%s".',
            \is_object($transformer) ? \get_class($transformer) : \gettype($transformer),
            DataTransformerInterface::class,
            FormDataTransformerInterface::class,
            'callable'
        ));
    }
}
