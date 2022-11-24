<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class AssociationTypeTestCase extends AbstractConfigTypeTestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $groupingConfigProvider;

    protected function setUp(): void
    {
        $this->groupingConfigProvider = $this->createMock(ConfigProvider::class);

        parent::setUp();
    }

    protected function setConfigProvidersForSubmitTest(array &$configProviders): void
    {
        parent::setConfigProvidersForSubmitTest($configProviders);
        $configProviders['grouping'] = $this->groupingConfigProvider;
    }
}
