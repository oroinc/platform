<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

class AssociationTypeTestCase extends AbstractConfigTypeTestCase
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

    protected function setConfigProvidersForSubmitTest(array &$configProviders)
    {
        parent::setConfigProvidersForSubmitTest($configProviders);
        $configProviders['grouping'] = $this->groupingConfigProvider;
    }
}
