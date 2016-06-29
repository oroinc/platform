<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;

class DataTransformerRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $transformer1;

    /** @var DataTransformerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->transformer1 = $this->getMock('Symfony\Component\Form\DataTransformerInterface');

        $this->registry = new DataTransformerRegistry();
        $this->registry->addDataTransformer('dataType1', $this->transformer1);
    }

    public function testGetDataTransformer()
    {
        $this->assertSame(
            $this->transformer1,
            $this->registry->getDataTransformer('dataType1')
        );
        $this->assertNull(
            $this->registry->getDataTransformer('undefined')
        );
    }
}
