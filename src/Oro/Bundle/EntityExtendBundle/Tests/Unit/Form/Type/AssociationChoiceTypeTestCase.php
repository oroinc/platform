<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

class AssociationChoiceTypeTestCase extends AbstractConfigTypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $groupingConfigProvider;

    protected function setUp()
    {
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function prepareBuildViewTest()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                        ['test', $this->testConfigProvider],
                    ]
                )
            );
    }
}
