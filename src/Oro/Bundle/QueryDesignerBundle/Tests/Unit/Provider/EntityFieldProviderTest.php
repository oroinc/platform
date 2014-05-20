<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Provider;

use Oro\Bundle\QueryDesignerBundle\Provider\EntityFieldProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\Tests\Util\ReflectionUtil;

class EntityFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityFieldProvider */
    protected $provider;

    /** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
    protected $qdManager;

    public function setUp()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(false));

        $entityClassResolver  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->qdManager = $this->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $virtualFieldsProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new EntityFieldProvider(
            $configProvider,
            $configProvider,
            $entityClassResolver,
            $doctrine,
            $translator,
            $virtualFieldsProvider,
            [],
            $this->qdManager
        );
        $this->provider->setQueryType('report');
    }

    public function testIsIgnoredField()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldName = 'test';

        $this->qdManager->expects($this->once())
            ->method('isIgnoredField')
            ->with($metadata, $fieldName, 'report')
            ->will($this->returnValue(true));

        $result = ReflectionUtil::callProtectedMethod($this->provider, 'isIgnoredField', [$metadata, $fieldName]);
        $this->assertTrue($result);
    }

    public function testIsIgnoredRelation()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldName = 'test';

        $this->qdManager->expects($this->once())
            ->method('isIgnoredAssosiation')
            ->with($metadata, $fieldName, 'report')
            ->will($this->returnValue(true));

        $result = ReflectionUtil::callProtectedMethod($this->provider, 'isIgnoredRelation', [$metadata, $fieldName]);
        $this->assertTrue($result);
    }
}
