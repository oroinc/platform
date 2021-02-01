<?php
declare(strict_types=1);

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\DependencyInjection\OroCurrencyExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCurrencyExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroCurrencyExtension());

        $expectedParameters = [
            'oro_currency.price.model',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_currency.twig.currency',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = [
            'oro_currency',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    public function testGetAlias(): void
    {
        static::assertEquals('oro_currency', (new OroCurrencyExtension())->getAlias());
    }
}
