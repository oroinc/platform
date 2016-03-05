<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\RemoveExcludedSorters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RemoveExcludedSortersTest extends ConfigProcessorTestCase
{
    /** @var RemoveExcludedSorters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveExcludedSorters();
    }

    public function testProcess()
    {
        $sorters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getSorters()
        );
    }
}
