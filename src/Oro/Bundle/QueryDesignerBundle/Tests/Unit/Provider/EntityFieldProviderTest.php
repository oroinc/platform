<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ExclusionProvider;
use Oro\Bundle\QueryDesignerBundle\Provider\EntityFieldProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\Tests\Util\ReflectionUtil;

class EntityFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityFieldProvider */
    protected $provider;

    /** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
    protected $qdManager;

    /** @var ExclusionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $exclusionProvider;

    public function setUp()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(false));

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
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

        $virtualFieldsProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ConfigVirtualFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->exclusionProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ExclusionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new EntityFieldProvider(
            $configProvider,
            $configProvider,
            $entityClassResolver,
            $doctrine,
            $translator,
            $virtualFieldsProvider,
            $this->exclusionProvider,
            [],
            $this->qdManager
        );
        $this->provider->setQueryType('report');
    }

    public function testIsIgnoredFieldTrue()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Entity\Fake'));

        $this->exclusionProvider->expects($this->at(0))
            ->method('isIgnoredField')
            ->will($this->returnValue(false));

        $this->exclusionProvider->expects($this->at(1))
            ->method('isIgnoredField')
            ->will($this->returnValue(true));

        $this->qdManager->expects($this->once())
            ->method('getExcludeRules')
            ->will($this->returnValue([]));

        $result = ReflectionUtil::callProtectedMethod($this->provider, 'isIgnoredField', [$metadata, 'fakeField']);
        $this->assertTrue($result);
    }


    public function testIsIgnoredFieldQueryType()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Entity\Fake'));

        $this->exclusionProvider->expects($this->at(0))
            ->method('isIgnoredField')
            ->will($this->returnValue(false));

        $this->exclusionProvider->expects($this->at(1))
            ->method('isIgnoredField')
            ->will($this->returnValue(false));

        $this->qdManager->expects($this->once())
            ->method('getExcludeRules')
            ->will($this->returnValue([['query_type' => 'report']]));

        $result = ReflectionUtil::callProtectedMethod($this->provider, 'isIgnoredField', [$metadata, 'fakeField']);
        $this->assertTrue($result);
    }

    public function testIsIgnoredFieldFalse()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Entity\Fake'));

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredField')
            ->will($this->returnValue(true));

        $result = ReflectionUtil::callProtectedMethod($this->provider, 'isIgnoredField', [$metadata, 'fakeField']);
        $this->assertTrue($result);
    }

    public function testIsIgnoredRelation()
    {
        $metadata  = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldName = 'default_test';

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($metadata, $fieldName)
            ->will($this->returnValue(true));

        $result = ReflectionUtil::callProtectedMethod($this->provider, 'isIgnoredRelation', [$metadata, $fieldName]);
        $this->assertTrue($result);
    }
}
