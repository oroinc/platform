<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

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
