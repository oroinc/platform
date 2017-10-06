<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class EntityIdTransformerRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $transformer1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $transformer2;

    /** @var EntityIdTransformerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->transformer1 = $this->createMock(EntityIdTransformerInterface::class);
        $this->transformer2 = $this->createMock(EntityIdTransformerInterface::class);

        $this->registry = new EntityIdTransformerRegistry(
            [
                [$this->transformer1, 'rest&!json_api'],
                [$this->transformer2, 'json_api'],
            ],
            new RequestExpressionMatcher()
        );
    }

    public function testGetEntityIdTransformerForKnownRequestType()
    {
        self::assertSame(
            $this->transformer2,
            $this->registry->getEntityIdTransformer(new RequestType(['rest', 'json_api']))
        );
    }

    public function testGetEntityIdTransformerForUnknownRequestType()
    {
        self::assertNull(
            $this->registry->getEntityIdTransformer(new RequestType(['another']))
        );
    }
}
