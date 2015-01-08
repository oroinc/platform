<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\SoapBundle\Provider\MetadataProviderInterface;

class StubMetadataProvider implements MetadataProviderInterface
{
    /** @var array */
    protected $metadata = [];

    /**
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($object)
    {
        return $this->metadata;
    }
}
