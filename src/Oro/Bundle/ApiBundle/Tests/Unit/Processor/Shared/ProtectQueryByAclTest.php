<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\ProtectQueryByAcl;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProtectQueryByAclTest extends OrmRelatedTestCase
{
    /** @var ProtectQueryByAcl */
    private $processor;

    /** @var Context */
    private $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    private $aclHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclAnnotationProvider */
    private $aclAnnotationProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->aclAnnotationProvider = $this->createMock(AclAnnotationProvider::class);
        $this->processor = new ProtectQueryByAcl(
            $this->doctrineHelper,
            $this->aclHelper,
            $this->aclAnnotationProvider,
            'VIEW'
        );

        $configProvider = $this->createMock(ConfigProvider::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new Context($configProvider, $metadataProvider);
    }

    /**
     * @return Criteria
     */
    private function getCriteria()
    {
        return new Criteria($this->createMock(EntityClassResolver::class));
    }

    public function testProcessWhenQueryIsNotDoctrineQuery()
    {
        $className = Product::class;
        $this->context->setClassName($className);
        $query = new \stdClass();
        $this->context->setQuery($query);

        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->processor->process($this->context);
    }

    public function testProcessWithoutConfig()
    {
        $query = $this->createMock(QueryBuilder::class);
        $this->context->setQuery($query);
        $className = Product::class;
        $this->context->setClassName($className);
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        $criteria = $this->getCriteria();
        $this->context->setCriteria($criteria);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($query, 'VIEW');

        $this->processor->process($this->context);
    }

    public function testProcessWithConfig()
    {
        $query = $this->createMock(QueryBuilder::class);
        $this->context->setQuery($query);
        $className = Product::class;
        $this->context->setClassName($className);
        $config = new EntityDefinitionConfig();
        $aclResource = 'acme_test_delete_resource';
        $config->setAclResource($aclResource);
        $this->context->setConfig($config);
        $criteria = $this->getCriteria();
        $this->context->setCriteria($criteria);
        $aclAnnotation = new Acl(
            [
                'id' => $aclResource,
                'class' => $className,
                'permission' => 'DELETE',
                'type' => 'entity'

            ]
        );

        $this->aclAnnotationProvider->expects(self::once())
            ->method('findAnnotationById')
            ->with($aclResource)
            ->willReturn($aclAnnotation);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($query, 'DELETE');

        $this->processor->process($this->context);
    }
}
