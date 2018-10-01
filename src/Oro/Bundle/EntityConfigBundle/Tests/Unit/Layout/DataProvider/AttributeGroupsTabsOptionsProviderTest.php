<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\AttributeGroupsTabsOptionsProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeGroupsTabsOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetConfig()
    {
        $attributeFamily = new AttributeFamily();
        /** @var AttributeGroup $firstGroup */
        $firstGroup = $this->getEntity(
            AttributeGroup::class,
            [
                'id' => 1,
                'code'=> 'first_code',
                'labels' => new ArrayCollection([(new LocalizedFallbackValue())->setString('One')])
            ]
        );
        /** @var AttributeGroup $secondGroup */
        $secondGroup = $this->getEntity(
            AttributeGroup::class,
            [
                'id' => 2,
                'code'=> 'second_code',
                'labels' => new ArrayCollection([(new LocalizedFallbackValue())->setString('Two')])
            ]
        );

        /** @var AttributeRenderRegistry|\PHPUnit\Framework\MockObject\MockObject $attributeRenderRegistry */
        $attributeRenderRegistry = $this->createMock(AttributeRenderRegistry::class);
        $attributeRenderRegistry->expects($this->once())
            ->method('getNotRenderedGroups')
            ->with($attributeFamily)
            ->willReturn(new ArrayCollection([$firstGroup, $secondGroup]));

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnMap([
                [$firstGroup->getLabels(), null, 'First'],
                [$secondGroup->getLabels(), null, 'Second']
            ]);

        $attributeGroupsTabsOptionsProvider = new AttributeGroupsTabsOptionsProvider(
            $attributeRenderRegistry,
            $localizationHelper
        );

        $this->assertEquals(
            [
                [
                    'id' => 'first_code',
                    'label' => 'First'
                ],
                [
                    'id' => 'second_code',
                    'label' => 'Second'
                ]
            ],
            $attributeGroupsTabsOptionsProvider->getTabsOptions($attributeFamily)
        );
    }
}
