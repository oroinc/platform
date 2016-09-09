<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Processor\AddScopeFilter;

class AddScopeFilterTest extends GetListProcessorTestCase
{
    /** @var AddScopeFilter */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AddScopeFilter();
    }

    public function testProcessWhenScopeFilterIsAlreadyExist()
    {
        $filter = new StandaloneFilterWithDefaultValue(
            'string',
            'filter description',
            'global'
        );

        $this->context->getFilters()->add('scope', $filter);
        $this->processor->process($this->context);

        $this->assertSame(
            $filter,
            $this->context->getFilters()->get('scope')
        );
    }

    public function testProcessWhenScopeFilterDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            new StandaloneFilterWithDefaultValue(
                'string',
                'Configuration Scope',
                'user'
            ),
            $this->context->getFilters()->get('scope')
        );
    }
}
