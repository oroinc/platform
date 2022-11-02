<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider;

use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Provider\WidgetConfigurationFormProvider;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Yaml\Yaml;

class WidgetConfigurationFormProviderTest extends FormIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new DataBlockExtension())
            ->getFormFactory();
    }

    public function testGetFormShouldReturnExceptionIfNoFormIsDefinedForWidget()
    {
        $this->expectException(InvalidArgumentException::class);
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');
        $this->assertFalse($provider->hasForm('quick_launchpad_without_form'));

        $provider->getForm('quick_launchpad_without_form');
    }

    public function testGetForm()
    {
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');
        $this->assertTrue($provider->hasForm('quick_launchpad'));
        $form = $provider->getForm('quick_launchpad');
        $this->assertInstanceOf(FormInterface::class, $form);

        $this->assertTrue($form->has('some_field'));
        $this->assertTrue($form->has('some_another_field'));
        $this->assertCount(2, $form);
    }

    private function getConfig(string $path): array
    {
        return Yaml::parse(file_get_contents($path));
    }

    private function getProviderWithConfigLoaded(string $configPath): WidgetConfigurationFormProvider
    {
        $config = $this->getConfig($configPath);
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects(self::any())
            ->method('getWidgetConfig')
            ->willReturnCallback(function ($name) use ($config) {
                return $config['dashboards']['widgets'][$name];
            });

        return new WidgetConfigurationFormProvider(
            $configProvider,
            $this->factory
        );
    }
}
