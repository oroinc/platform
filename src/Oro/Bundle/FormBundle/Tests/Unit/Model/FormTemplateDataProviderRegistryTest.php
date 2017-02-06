<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class FormTemplateDataProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormTemplateDataProviderRegistry
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = new FormTemplateDataProviderRegistry();
    }

    public function testRegisterAndGet()
    {
        /** @var FormTemplateDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockBuilder(FormTemplateDataProviderInterface::class)->getMock();
        $this->registry->addProvider($provider, 'test');

        $this->assertSame($this->registry->get('test'), $provider);
    }

    public function testRegisterOverridesPreviousByAlias()
    {
        /** @var FormTemplateDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider1 */

        $provider1 = $this->getMockBuilder(FormTemplateDataProviderInterface::class)->getMock();
        $this->registry->addProvider($provider1, 'test');
        /** @var FormTemplateDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider2 */
        $provider2 = $this->getMockBuilder(FormTemplateDataProviderInterface::class)->getMock();
        $this->registry->addProvider($provider2, 'test');

        $this->assertSame($this->registry->get('test'), $provider2);
        $this->assertNotSame($this->registry->get('test'), $provider1);
    }

    /**
     * @expectedException \Oro\Bundle\FormBundle\Exception\UnknownProviderException
     * @expectedExceptionMessage Unknown provider with alias `test`
     */
    public function testGetUnregisteredException()
    {
        $this->registry->get('test');
    }
}
