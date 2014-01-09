<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;

class TranslationServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $adapter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dumper;

    /** @var TranslationServiceProvider */
    protected $service;

    public function setUp()
    {
        $this->adapter = $this->getMock('Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter', [], [], '', false);
        $this->dumper  = $this->getMock('Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper', [], [], '', false);

        $this->service = new TranslationServiceProvider($this->adapter, $this->dumper, 'someTestRootDir');
    }

    public function tearDown()
    {
        unset($this->adapter, $this->dumper, $this->service);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';

        $this->adapter->expects($this->once())
            ->method('upload')
            ->with($this->isType('array'), $mode);

        $this->service->setAdapter($this->adapter);
        $this->service->upload(__DIR__ . '/../Fixtures/Resources/lang-pack/', $mode);
    }
}
