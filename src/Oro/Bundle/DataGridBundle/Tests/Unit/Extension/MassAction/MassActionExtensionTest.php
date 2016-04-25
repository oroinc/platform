<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class MassActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface */
    protected $container;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var MassActionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->extension = new MassActionExtension($this->container, $this->securityFacade, $this->translator);
    }

    protected function tearDown()
    {
        unset($this->extension, $this->container, $this->securityFacade, $this->translator);
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
