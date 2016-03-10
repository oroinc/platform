<?php

namespace Oro\Component\TestUtils\Mocks;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink as BaseServiceLink;

class ServiceLink extends BaseServiceLink
{
    /** @var object */
    protected $service;

    /**
     * @param object $service
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getService()
    {
        return $this->service;
    }
}
