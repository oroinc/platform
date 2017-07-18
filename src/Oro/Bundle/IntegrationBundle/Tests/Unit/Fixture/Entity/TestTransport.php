<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Entity;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

class TestTransport extends Transport
{
    /** @var ParameterBag */
    protected $parameters;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = new ParameterBag($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        return $this->parameters;
    }
}
