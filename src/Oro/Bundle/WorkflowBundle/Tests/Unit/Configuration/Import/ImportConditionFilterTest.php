<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\Import\ImportConditionFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class ImportConditionFilterTest extends TestCase
{
    public function testFilter(): void
    {
        $container = new Container();
        $container->setParameter('parameter1', true);
        $container->setParameter('parameter2', false);

        $filter = new ImportConditionFilter($container);
        $imports = [
            'test1.yml',
            [
                'resource' => 'test2.yml',
                'import_condition' => "parameter_or_null('parameter1') !== true"
            ],
            [
                'resource' => 'test3.yml',
                'import_condition' => "parameter_or_null('parameter2') !== true"
            ],
            [
                'resource' => 'test4.yml',
                'import_condition' => "parameter_or_null('parameter3') !== true"
            ]
        ];

        $expectedImports = [
            'test1.yml',
            [
                'resource' => 'test3.yml',
                'import_condition' => "parameter_or_null('parameter2') !== true"
            ],
            [
                'resource' => 'test4.yml',
                'import_condition' => "parameter_or_null('parameter3') !== true"
            ]
        ];

        $this->assertEqualsCanonicalizing($expectedImports, $filter->filter($imports));
    }
}
