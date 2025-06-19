<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryResolver;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\EntityConfig;
use PHPUnit\Framework\MockObject\MockObject;

class AclProtectedQueryResolverTest extends OrmRelatedTestCase
{
    private QueryHintResolverInterface&MockObject $queryHintResolver;
    private AclHelper&MockObject $aclHelper;
    private AclProtectedQueryResolver $queryResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->queryResolver = new AclProtectedQueryResolver(
            $this->queryHintResolver,
            $this->aclHelper
        );
    }

    public function testResolveQuery(): void
    {
        $query = new Query($this->em);
        $config = new EntityConfig();
        $config->addHint('test');

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query), 'VIEW', [AclAccessRule::CHECK_OWNER => true]);
        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        $this->queryResolver->resolveQuery($query, $config);
    }

    public function testResolveQueryWhenAclForRootEntityShouldBeSkipped(): void
    {
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->addHint('test');
        $config->set(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY, true);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query), 'VIEW', [AclAccessRule::CHECK_OWNER => true, 'checkRootEntity' => false]);
        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        $this->queryResolver->resolveQuery($query, $config);
    }
}
