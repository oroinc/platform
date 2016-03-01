<?php

namespace Oro\Bundle\DashboardBundle\Tests\Provider;

use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Provider\WidgetConfigurationFormProvider;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Yaml\Yaml;

class DashboardConfigurationFormProviderTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(
                new DataBlockExtension()
            )
            ->getFormFactory();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->securityFacade);
    }

    /**
     * @expectedException Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException
     */
    public function testGetFormShouldReturnExceptionIfNoFormIsDefinedForWidget()
    {
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');
        $this->assertFalse($provider->hasForm('quick_launchpad_without_form'));

        $provider->getForm('quick_launchpad_without_form');
    }

    public function testGetForm()
    {
        $provider = $this->getProviderWithConfigLoaded(__DIR__ . '/../Fixtures/Provider/good_definition.yml');
        $this->assertTrue($provider->hasForm('quick_launchpad'));
        $form = $provider->getForm('quick_launchpad');
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);

        $this->assertTrue($form->has('some_field'));
        $this->assertTrue($form->has('some_another_field'));
        $this->assertCount(2, $form);
    }

    /**
     * Parse config fixture and validate through processorDecorator
     *
     * @param string $path
     *
     * @return array
     */
    protected function getConfig($path)
    {
        $config = Yaml::parse(file_get_contents($path));

        return $config;
    }

    /**
     * @param string $configPath
     *
     * @return SystemConfigurationFormProvider
     */
    protected function getProviderWithConfigLoaded($configPath)
    {
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $config = $this->getConfig($configPath);
        $provider = new WidgetConfigurationFormProvider(
            new ConfigProvider($config['oro_dashboard_config'], $eventDispatcher),
            $this->factory
        );

        return $provider;
    }
}
