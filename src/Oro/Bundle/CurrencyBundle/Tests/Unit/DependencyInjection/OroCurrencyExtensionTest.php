<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\DependencyInjection\OroCurrencyExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCurrencyExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    /**
     * Test Load
     */
    public function testLoad()
    {
        $this->loadExtension(new OroCurrencyExtension());

        $expectedParameters = [
            'oro_currency.price.model',
            'oro_currency.form.type.currency_selection.class',
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

    /**
     * {@inheritDoc}
     */
    protected function buildContainerMock()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig'])
            ->getMock();
    }

    /**
     * {@inheritDoc}
     */
    protected function getContainerMock()
    {
        $container = parent::getContainerMock();
        $container->expects($this->once())
            ->method('prependExtensionConfig')
            ->will(
                $this->returnCallback(
                    function ($name, array $config) {
                        if (!isset($this->extensionConfigs[$name])) {
                            $this->extensionConfigs[$name] = [];
                        }

                        array_unshift($this->extensionConfigs[$name], $config);
                    }
                )
            );

        return $container;
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroCurrencyExtension();

        $this->assertEquals('oro_currency', $extension->getAlias());
    }

    /**
     * @param array $expectedExtensionConfigs
     */
    protected function assertExtensionConfigsLoaded(array $expectedExtensionConfigs)
    {
        foreach ($expectedExtensionConfigs as $extensionName) {
            $this->assertArrayHasKey(
                $extensionName,
                $this->extensionConfigs,
                sprintf('Config for extension "%s" has not been loaded.', $extensionName)
            );

            $this->assertNotEmpty(
                $this->extensionConfigs[$extensionName],
                sprintf('Config for extension "%s" is empty.', $extensionName)
            );
        }
    }
}
