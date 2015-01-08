<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Request\Parameters\Filter;

use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;

class IdentifierToReferenceFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $testClassName = 'Oro\\Bundle\\SoapBundle\\Tests\\Unit\\Entity\\Manager\\Stub\\Entity';
        $testReference = new \stdClass();
        $testId        = 111;

        $em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $filter   = new IdentifierToReferenceFilter($registry, $testClassName);

        $registry->expects($this->once())->method('getManagerForClass')->with($testClassName)->willReturn($em);
        $em->expects($this->once())->method('getReference')->with($testClassName, $testId)->willReturn($testReference);

        $this->assertSame($testReference, $filter->filter($testId, null));
    }
}
