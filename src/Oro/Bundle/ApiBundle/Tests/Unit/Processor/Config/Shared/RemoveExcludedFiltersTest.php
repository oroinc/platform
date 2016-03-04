<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\RemoveExcludedFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RemoveExcludedFiltersTest extends ConfigProcessorTestCase
{
    /** @var RemoveExcludedFilters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveExcludedFilters();
    }

    public function testProcess()
    {
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'integer'
                ],
                'field2' => [
                    'data_type' => 'integer',
                    'exclude'   => true
                ]
            ]
        ];

        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type' => 'integer'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }
}
