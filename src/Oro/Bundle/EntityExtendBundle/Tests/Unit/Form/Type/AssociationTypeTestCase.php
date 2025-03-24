<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;

abstract class AssociationTypeTestCase extends AbstractConfigTypeTestCase
{
    protected ConfigProvider&MockObject $groupingConfigProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->groupingConfigProvider = $this->createMock(ConfigProvider::class);
        parent::setUp();
    }

    #[\Override]
    protected function setConfigProvidersForSubmitTest(array &$configProviders): void
    {
        parent::setConfigProvidersForSubmitTest($configProviders);
        $configProviders['grouping'] = $this->groupingConfigProvider;
    }
}
