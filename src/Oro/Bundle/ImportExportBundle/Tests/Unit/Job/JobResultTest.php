<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

class JobResultTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new JobResult();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['context', $this->createMock(ContextInterface::class)],
            ['jobId', 'test'],
            ['jobCode', 'test'],
            ['successful', true]
        ];
    }

    public function testFailureExceptions()
    {
        $obj = new JobResult();
        $obj->addFailureException('Error 1');
        $obj->addFailureException('Error 2');
        $this->assertEquals(['Error 1', 'Error 2'], $obj->getFailureExceptions());
    }
}
