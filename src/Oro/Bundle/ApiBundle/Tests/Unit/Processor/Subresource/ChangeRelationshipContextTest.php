<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;

class ChangeRelationshipContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var ChangeRelationshipContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new ChangeRelationshipContext($this->configProvider, $this->metadataProvider);
    }

    public function testGetParentConfigExtras()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');

        $this->assertEquals(
            [
                new EntityDefinitionConfigExtra('update'),
                new FilterFieldsConfigExtra(
                    [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                )
            ],
            $this->context->getParentConfigExtras()
        );
    }
}
