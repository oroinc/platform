<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class MassActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var MassActionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->container = $this->getMock(ContainerInterface::class);

        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->extension = new MassActionExtension(
            $this->container,
            $this->securityFacade,
            $this->translator,
            $this->eventDispatcher
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->container, $this->securityFacade, $this->translator, $this->eventDispatcher);
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->extension->isApplicable(DatagridConfiguration::create([])));
    }

    public function testVisitResult()
    {
        $result = ResultsObject::create([]);

        $this->extension->visitResult(DatagridConfiguration::create([]), $result);

        $this->assertArrayHasKey('metadata', $result);
        $this->assertInternalType('array', $result['metadata']);
    }
}
