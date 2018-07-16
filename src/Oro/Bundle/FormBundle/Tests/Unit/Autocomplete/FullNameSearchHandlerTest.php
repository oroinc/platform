<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler;

class FullNameSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_CLASS = 'FooEntityClass';

    /**
     * @var array
     */
    protected $testProperties = array('name', 'email');

    /**
     * @var FullNameSearchHandler
     */
    protected $searchHandler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityNameResolver;

    protected function setUp()
    {
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchHandler = new FullNameSearchHandler(self::TEST_ENTITY_CLASS, $this->testProperties);
    }

    public function testConvertItem()
    {
        $fullName = 'Mr. John Doe';

        $entity = new \stdClass();
        $entity->name = 'John';
        $entity->email = 'john@example.com';

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($entity)
            ->will($this->returnValue($fullName));

        $this->searchHandler->setEntityNameResolver($this->entityNameResolver);
        $this->assertEquals(
            array(
                'name' => 'John',
                'email' => 'john@example.com',
                'fullName' => $fullName,
            ),
            $this->searchHandler->convertItem($entity)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Name resolver must be configured
     */
    public function testConvertItemFails()
    {
        $this->searchHandler->convertItem(new \stdClass());
    }
}
