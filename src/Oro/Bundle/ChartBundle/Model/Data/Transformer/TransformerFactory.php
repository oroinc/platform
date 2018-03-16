<?php

namespace Oro\Bundle\ChartBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransformerFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
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
