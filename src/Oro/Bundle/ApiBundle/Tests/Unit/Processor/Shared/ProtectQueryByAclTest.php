<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\ProtectQueryByAcl;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProtectQueryByAclTest extends GetListProcessorOrmRelatedTestCase
{
    private const DEFAULT_PERMISSION = 'TEST';

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    private $aclHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclAttributeProvider */
    private $AclAttributeProvider;

    /** @var ProtectQueryByAcl */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->AclAttributeProvider = $this->createMock(AclAttributeProvider::class);

        $this->processor = new ProtectQueryByAcl(
            $this->doctrineHelper,
            $this->aclHelper,
            $this->AclAttributeProvider,
            self::DEFAULT_PERMISSION
        );
    }

    public function testProcessWhenQueryIsNotDoctrineQuery()
    {
        $query = new \stdClass();

        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->context->setClassName(Product::class);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutConfig()
    {
        $query = $this->createMock(QueryBuilder::class);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($query, self::DEFAULT_PERMISSION);

        $this->context->setClassName(Product::class);
        $this->context->setConfig(null);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessWhenConfigDoesNotContainAclResource()
    {
        $config = new EntityDefinitionConfig();
        $query = $this->createMock(QueryBuilder::class);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($query, self::DEFAULT_PERMISSION);

        $this->context->setClassName(Product::class);
        $this->context->setConfig($config);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessConfigContainsAclResource()
    {
        $className = Product::class;
        $permission = 'DELETE';
        $aclResource = 'acme_test_delete_resource';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);
        $aclAttribute = Acl::fromArray([
            'id'         => $aclResource,
            'class'      => $className,
            'permission' => $permission,
            'type'       => 'entity'
        ]);
        $query = $this->createMock(QueryBuilder::class);

        $this->AclAttributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with($aclResource)
            ->willReturn($aclAttribute);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($query, $permission);

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessConfigContainsUnknownAclResource()
    {
        $className = Product::class;
        $aclResource = 'acme_test_delete_resource';
        $config = new EntityDefinitionConfig();
        $config->setAclResource($aclResource);
        $query = $this->createMock(QueryBuilder::class);

        $this->AclAttributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with($aclResource)
            ->willReturn(null);
        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }

    public function testProcessWhenAclIsDisabled()
    {
        $className = Product::class;
        $config = new EntityDefinitionConfig();
        $config->setAclResource(null);
        $query = $this->createMock(QueryBuilder::class);

        $this->AclAttributeProvider->expects(self::never())
            ->method('findAttributeById');
        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->context->setQuery($query);
        $this->processor->process($this->context);
    }
}
