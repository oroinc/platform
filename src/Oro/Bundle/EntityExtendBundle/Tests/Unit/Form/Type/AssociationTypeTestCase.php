<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

abstract class AssociationTypeTestCase extends AbstractConfigTypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
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
