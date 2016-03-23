<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\EntityTypeSecurityCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityTypeSecurityCheckTest extends OrmRelatedTestCase
{
    /** @var EntityTypeSecurityCheck */
    protected $processor;

    /** @var Context */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    public function setUp()
    {
        parent::setUp();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new EntityTypeSecurityCheck($this->doctrineHelper, $this->securityFacade, 'VIEW');

        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new Context($configProvider, $metadataProvider);
    }

    public function testProcessOnDefaultConfig()
    {
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->context->setClassName($className);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $className))
            ->willReturn(true);

        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage Access Denied.
     */
    public function testProcessNotGrantedOnDefaultConfig()
    {
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->context->setClassName($className);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity('entity', $className))
            ->willReturn(false);

        $this->processor->process($this->context);
    }

    public function testProcessWithConfig()
    {
        $aclResource = 'acme_product_test';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);
        $this->context->setConfig($config);
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->context->setClassName($className);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($aclResource)
            ->willReturn(true);

        $this->processor->process($this->context);
    }
}
