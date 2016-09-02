<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActionBundle\Layout\DataProvider\ActionButtonsProvider;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionButtonsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationsHelper;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ActionButtonsProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->applicationsHelper = $this->getMock(ApplicationsHelper::class, [], [], '', false);
        $this->doctrineHelper = $this->getMock(DoctrineHelper::class, [], [], '', false);

        $this->provider = new ActionButtonsProvider($this->applicationsHelper, $this->doctrineHelper);
    }

    public function testGetDialogRoute()
    {
        $result = 'dialog_route';
        
        $this->applicationsHelper
            ->expects($this->once())
            ->method('getDialogRoute')
            ->will($this->returnValue($result));
        
        $this->assertSame($result, $this->provider->getDialogRoute());
    }

    public function testGetExecutionRoute()
    {
        $result = 'execution_route';

        $this->applicationsHelper
            ->expects($this->once())
            ->method('getExecutionRoute')
            ->will($this->returnValue($result));

        $this->assertSame($result, $this->provider->getExecutionRoute());
    }

    public function testGetEntityClassFromObject()
    {
        $entity = new \stdClass();

        $this->assertEquals(ClassUtils::getClass($entity), $this->provider->getEntityClass($entity));
    }

    public function testGetEntityClassFromString()
    {
        $class = 'Oro\Bundle\ActionBundle\Layout\DataProvider\ActionButtonsProvider';

        $this->assertEquals(ClassUtils::getRealClass($class), $this->provider->getEntityClass($class));
    }

    public function testGetEntityId()
    {
        $entity = new \stdClass();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $this->assertEquals(1, $this->provider->getEntityId($entity));
    }

    public function testGetEntityIdNonObject()
    {
        /** @var object $entity */
        $entity = 'entity';

        $this->assertNull($this->provider->getEntityId($entity));
    }
}
