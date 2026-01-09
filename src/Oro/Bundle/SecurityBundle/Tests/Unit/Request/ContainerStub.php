<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class ContainerStub extends Container
{
    private $parameters;

    public function __construct(array $parameters = [])
    {
        parent::__construct();
        $this->parameters = $parameters;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    #[\Override]
    public function getParameter($name): \UnitEnum|float|int|bool|array|string|null
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }

        return $this->parameters[$name];
    }
}
