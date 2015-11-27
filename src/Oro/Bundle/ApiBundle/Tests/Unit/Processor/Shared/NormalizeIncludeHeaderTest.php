<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeIncludeHeader;

class NormalizeIncludeHeaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var NormalizeIncludeHeader */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new NormalizeIncludeHeader();
    }

    public function testProcessWhenNoIncludeHeader()
    {
        $context = $this->getContext();

        $this->processor->process($context);

        $this->assertFalse($context->getRequestHeaders()->has(Context::INCLUDE_HEADER));
    }

    public function testProcessWhenIncludeHeaderAlreadyNormalized()
    {
        $context = $this->getContext();
        $context->getRequestHeaders()->set(Context::INCLUDE_HEADER, ['val1', 'val2']);

        $this->processor->process($context);

        $this->assertEquals(
            ['val1', 'val2'],
            $context->getRequestHeaders()->get(Context::INCLUDE_HEADER)
        );
    }

    public function testProcess()
    {
        $context = $this->getContext();
        $context->getRequestHeaders()->set(Context::INCLUDE_HEADER, 'val1; val2');

        $this->processor->process($context);

        $this->assertEquals(
            ['val1', 'val2'],
            $context->getRequestHeaders()->get(Context::INCLUDE_HEADER)
        );
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return new Context($configProvider, $metadataProvider);
    }
}
