<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\Event\ConfigureActionsBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var ActionExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->container = $this->getMock(ContainerInterface::class);
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->extension = new ActionExtension(
            $this->container,
            $this->securityFacade,
            $this->translator,
            $this->eventDispatcher
        );

        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        $config = DatagridConfiguration::create([
            'actions' => [
                'action1' => [],
            ],
        ]);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ConfigureActionsBefore::NAME, new ConfigureActionsBefore($config));

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableAndNoConfig()
    {
        $config = DatagridConfiguration::create([]);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ConfigureActionsBefore::NAME, new ConfigureActionsBefore($config));

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableAndNotEnabled()
    {
        $config = DatagridConfiguration::create([]);

        $this->extension->getParameters()->set(ActionExtension::ENABLE_ACTIONS_PARAMETER, false);

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->assertFalse($this->extension->isApplicable($config));
    }
}
