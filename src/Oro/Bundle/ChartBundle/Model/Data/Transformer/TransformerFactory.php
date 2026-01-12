<?php

namespace Oro\Bundle\ChartBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating transformer instances from service IDs.
 *
 * This factory retrieves transformer services from the dependency injection container
 * by their service ID and validates that they implement the {@see TransformerInterface}.
 * It provides a centralized way to instantiate transformers while ensuring type safety
 * and proper error handling for invalid or missing transformer services.
 */
class TransformerFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $serviceId
     * @return TransformerInterface
     * @throws InvalidArgumentException
     */
    public function createTransformer($serviceId)
    {
        $result = $this->container->get($serviceId);

        if (!$result instanceof TransformerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Service "%s" must be an instance of "%s".',
                    $serviceId,
                    'Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface'
                )
            );
        }

        return $result;
    }
}
