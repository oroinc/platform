<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryResolver;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\EntityConfig;

class AclProtectedQueryResolverTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryHintResolverInterface */
    private $queryHintResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    private $aclHelper;

    /** @var AclProtectedQueryResolver */
    private $queryResolver;

    protected function setUp()
    {
        parent::setUp();

        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->queryResolver = new AclProtectedQueryResolver(
            $this->queryHintResolver,
            $this->aclHelper
        );
    }

    public function testResolveQuery()
    {
        $query = new Query($this->em);
        $config = new EntityConfig();
        $config->addHint('test');

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query), 'VIEW', []);
        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        $this->queryResolver->resolveQuery($query, $config);
    }

    public function testResolveQueryWhenAclForRootEntityShouldBeSkipped()
    {
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->addHint('test');
        $config->set(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY, true);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query), 'VIEW', ['checkRootEntity' => false]);
        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        $this->queryResolver->resolveQuery($query, $config);
    }
}
