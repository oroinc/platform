<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemUpdateContext;

class SingleItemUpdateContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var SingleItemUpdateContext */
    protected $context;

    protected function setUp()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new SingleItemUpdateContext($configProvider, $metadataProvider);
    }

    public function testForm()
    {
        $this->assertFalse($this->context->hasForm());
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setForm($form);
        $this->assertTrue($this->context->hasForm());
        $this->assertSame($form, $this->context->getForm());
    }


    public function testRequestData()
    {
        $requestData = [];
        $this->context->setRequestData($requestData);
        $this->assertSame($requestData, $this->context->getRequestData());
    }
}
