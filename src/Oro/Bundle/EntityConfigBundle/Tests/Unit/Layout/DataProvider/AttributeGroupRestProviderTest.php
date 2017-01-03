<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\AttributeGroupRestProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class AttributeGroupRestProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeGroupRestProvider
     */
    protected $provider;

    /**
     * @var AttributeGroupRenderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeGroupRenderRegistry;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    protected function setUp()
    {
        $this->attributeGroupRenderRegistry = $this->getMockBuilder(AttributeGroupRenderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AttributeGroupRestProvider(
            $this->attributeGroupRenderRegistry,
            $this->localizationHelper
        );
    }

    public function testGetTabsOptionsNonCached()
    {
        $attributeGroup1 = new AttributeGroup();
        $attributeGroup1->setCode('first_group');
        $attributeGroup2 = new AttributeGroup();
        $attributeGroup2->setCode('second_group');
        $attributeGroup3 = new AttributeGroup();
        $attributeGroup3->setCode('third_group');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup1);
        $attributeFamily->addAttributeGroup($attributeGroup2);
        $attributeFamily->addAttributeGroup($attributeGroup3);

        $this->attributeGroupRenderRegistry->expects($this->once())
            ->method('getNotRenderedGroups')
            ->with($attributeFamily)
            ->willReturn(new ArrayCollection([$attributeGroup1, $attributeGroup2]));

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->with(new ArrayCollection([]))
            ->willReturnOnConsecutiveCalls('label1', 'label2');

        $data = [
            'data' => [
                [
                    'id' => 'first_group',
                    'label' => 'label1'
                ],
                [
                    'id' => 'second_group',
                    'label' => 'label2'
                ]
            ]
        ];

        $this->assertSame($data, $this->provider->getTabsOptions($attributeFamily));

        $this->attributeGroupRenderRegistry->expects($this->never())
            ->method('getNotRenderedGroups');

        $this->localizationHelper->expects($this->never())
            ->method('getLocalizedValue');

        $this->assertSame($data, $this->provider->getTabsOptions($attributeFamily));
    }
}
