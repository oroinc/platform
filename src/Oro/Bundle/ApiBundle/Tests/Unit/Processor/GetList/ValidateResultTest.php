<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\ValidateResult;

class ValidateResultTest extends \PHPUnit_Framework_TestCase
{
    /** @var GetListContext */
    protected $context;

    /** @var ValidateResult */
    protected $processor;

    protected function setUp()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetListContext($configProvider, $metadataProvider);
        $this->processor = new ValidateResult();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Getting a list of entities failed.
     */
    public function testProcessOnExistingResult()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Unsupported request.
     */
    public function testProcessOnEmptyQuery()
    {
        $this->assertNull($this->context->getQuery());
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Unsupported query type: stdClass.
     */
    public function testProcessOnWrongQuery()
    {
        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);
    }
}
