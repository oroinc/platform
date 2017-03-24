<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var ActionExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->securityFacade = $this->createMock(SecurityFacade::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new ActionExtension($this->container, $this->securityFacade, $this->translator);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'action1' => [],
            ],
        ]);

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableAndNoConfig()
    {
        $config = DatagridConfiguration::create([]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableAndNotEnabled()
    {
        $config = DatagridConfiguration::create([]);

        $this->extension->getParameters()->set(ActionExtension::ENABLE_ACTIONS_PARAMETER, false);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableActionProviderHasActions()
    {
        /** @var DatagridActionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(DatagridActionProviderInterface::class);
        $this->extension->addActionProvider($provider);
        $config = DatagridConfiguration::create([]);

        $provider->expects($this->once())->method('hasActions')->with($config)->willReturn(true);

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableActionProviderHasNoActions()
    {
        /** @var DatagridActionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(DatagridActionProviderInterface::class);
        $this->extension->addActionProvider($provider);
        $config = DatagridConfiguration::create([]);

        $provider->expects($this->once())->method('hasActions')->with($config)->willReturn(false);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testFilteredProviders()
    {
        /** @var DatagridActionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider1*/
        /** @var DatagridActionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider2*/
        $provider1 = $this->createMock(DatagridActionProviderInterface::class);
        $provider2 = $this->createMock(DatagridActionProviderInterface::class);
        $this->extension->addActionProvider($provider1);
        $this->extension->addActionProvider($provider2);
        $config = DatagridConfiguration::create([]);

        $provider1->expects($this->exactly(2))->method('hasActions')->with($config)->willReturn(true);
        $provider2->expects($this->exactly(2))->method('hasActions')->with($config)->willReturn(false);

        $this->assertTrue($this->extension->isApplicable($config));

        $provider1->expects($this->once())->method('applyActions')->with($config);
        $provider2->expects($this->never())->method('applyActions');
        $this->extension->processConfigs($config);
    }

    public function testProcessConfigs()
    {
        /** @var DatagridActionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(DatagridActionProviderInterface::class);
        $this->extension->addActionProvider($provider);
        $config = DatagridConfiguration::create([]);

        $provider->expects($this->once())->method('hasActions')->with($config)->willReturn(true);
        $provider->expects($this->once())->method('applyActions')->with($config);

        $this->extension->processConfigs($config);
    }
}
