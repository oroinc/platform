<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Resolver;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Resolver\EntityFieldsTitleResolver;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;
use Oro\Bundle\SearchBundle\Tests\Unit\Stub\EntityStub;

class EntityFieldsTitleResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnConcatenatedNonEmptyTitleFields()
    {
        $entity = new EntityStub(1);
        $resolver = $this->getResolver([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emptyField' => '',
                'nullField' => null,
        ]);

        $this->assertEquals('John Doe', $resolver->resolve($entity));
    }

    /**
     * @param  array $titleFields
     * @return EntityTitleResolverInterface
     */
    private function getResolver(array $titleFields)
    {
        $mapper = $this->getMockBuilder(ObjectMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mapper->expects($this->any())
            ->method('getEntityMapParameter')
            ->will($this->returnValue(array_keys($titleFields)));

        $returnValues = call_user_func_array(
            [$this, 'onConsecutiveCalls'],
            array_values($titleFields)
        );

        $mapper->expects($this->any())
            ->method('getFieldValue')
            ->will($returnValues);

        return new EntityFieldsTitleResolver($mapper);
    }
}
