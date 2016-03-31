<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;

class FormContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormContext */
    protected $context;

    protected function setUp()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new FormContextStub($configProvider, $metadataProvider);
    }

    public function testRequestData()
    {
        $requestData = [];
        $this->context->setRequestData($requestData);
        $this->assertSame($requestData, $this->context->getRequestData());
    }

    public function testForm()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setForm($form);
        $this->assertSame($form, $this->context->getForm());
    }
}
