<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Controller\Api\Soap;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Controller\Api\Soap\Result as SoapResult;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertsResultToSoapResult()
    {
        $objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $query = new Query();

        $elements = [];

        for ($i=0; $i<5; $i++) {
            $elements[] = new Item(
                $objectManager,
                'ItemTest',
                $i,
                'record ' . $i,
                'http://localhost',
                [],
                [
                    'testValue1' => 'test' . $i,
                    'testValue2' => 'test' . $i
                ]
            );
        }

        $result = new Result($query, $elements);

        $soapResult = new SoapResult($result);

        $this->assertSame($soapResult->getElements(), $result->getElements());
        $this->assertEquals($soapResult->getRecordsCount(), $result->getRecordsCount());
    }
}
