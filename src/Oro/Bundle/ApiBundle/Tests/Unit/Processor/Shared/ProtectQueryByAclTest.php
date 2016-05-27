<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\ProtectQueryByAcl;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class ProtectQueryByAclTest extends OrmRelatedTestCase
{
    /** @var ProtectQueryByAcl */
    protected $processor;

    /** @var Context */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclAnnotationProvider;

    public function setUp()
    {
        parent::setUp();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclAnnotationProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new ProtectQueryByAcl(
            $this->doctrineHelper,
            $this->aclHelper,
            $this->aclAnnotationProvider,
            'VIEW'
        );

        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new Context($configProvider, $metadataProvider);
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->context->setClassName($className);
        $query = new \stdClass();
        $this->context->setQuery($query);

        $this->aclHelper->expects($this->never())
            ->method('applyAclToCriteria');

        $this->processor->process($this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
    }

    public function testProcessWithoutConfig()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->context->setClassName($className);
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        $criteria = new Criteria();
        $this->context->setCriteria($criteria);

        $this->aclHelper->expects($this->once())
            ->method('applyAclToCriteria')
            ->with($className, $criteria, 'VIEW');

        $this->processor->process($this->context);
    }

    public function testProcessWithConfig()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->context->setClassName($className);
        $config = new EntityDefinitionConfig();
        $aclResource = 'acme_test_delete_resource';
        $config->setAclResource($aclResource);
        $this->context->setConfig($config);
        $criteria = new Criteria();
        $this->context->setCriteria($criteria);
        $aclAnnotation = new Acl(
            [
                'id' => $aclResource,
                'class' => $className,
                'permission' => 'DELETE',
                'type' => 'entity'

            ]
        );

        $this->aclAnnotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with($aclResource)
            ->willReturn($aclAnnotation);

        $this->aclHelper->expects($this->once())
            ->method('applyAclToCriteria')
            ->with($className, $criteria, 'DELETE');

        $this->processor->process($this->context);
    }
}
