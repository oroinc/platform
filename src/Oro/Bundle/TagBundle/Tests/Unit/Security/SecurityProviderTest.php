<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Security;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Security\SecurityProvider as SearchSecurityProvider;
use Oro\Bundle\TagBundle\Security\SecurityProvider;
use PHPUnit\Framework\TestCase;

class SecurityProviderTest extends TestCase
{
    /**
     * @dataProvider aclDataProvider
     */
    public function testApplyAcl(array $protectedMap, array $grantedMap, array $expectedAllowedEntities): void
    {
        $entities = [
            ['entityName' => \stdClass::class],
            ['entityName' => \DateTime::class]
        ];
        $tableAlias = 'alias';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($entities);

        $searchQb = $this->createMock(QueryBuilder::class);
        $searchQb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $searchQb->expects(self::any())
            ->method($this->anything())
            ->willReturnSelf();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($searchQb);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb->expects(self::any())
            ->method('expr')
            ->willReturn(new Expr());
        if ($expectedAllowedEntities) {
            if (count($expectedAllowedEntities) !== count($entities)) {
                $qb->expects(self::once())
                    ->method('andWhere')
                    ->with($tableAlias . '.entityName IN(:allowedEntities)')
                    ->willReturnSelf();
                $qb->expects(self::once())
                    ->method('setParameter')
                    ->with('allowedEntities', $expectedAllowedEntities)
                    ->willReturnSelf();
            }
        } else {
            $qb->expects(self::once())
                ->method('andWhere')
                ->with('1 = 0')
                ->willReturnSelf();
        }

        $searchSecurityProvider = $this->createMock(SearchSecurityProvider::class);
        $searchSecurityProvider->expects(self::exactly(count($entities)))
            ->method('isProtectedEntity')
            ->willReturnMap($protectedMap);

        if ($grantedMap) {
            $searchSecurityProvider->expects(self::exactly(count($grantedMap)))
                ->method('isGranted')
                ->willReturnMap($grantedMap);
        } else {
            $searchSecurityProvider->expects(self::never())
                ->method('isGranted');
        }

        $provider = new SecurityProvider($searchSecurityProvider);
        $provider->applyAcl($qb, $tableAlias);
    }

    public function aclDataProvider(): array
    {
        return [
            'not protected' => [
                [],
                [],
                [\stdClass::class, \DateTime::class]
            ],
            'protected and granted' => [
                [
                    [\stdClass::class, true],
                    [\DateTime::class, true]
                ],
                [
                    ['VIEW', 'Entity:' . \stdClass::class, true],
                    ['VIEW', 'Entity:' . \DateTime::class, true]
                ],
                [\stdClass::class, \DateTime::class]
            ],
            'protected not granted' => [
                [
                    [\stdClass::class, true],
                    [\DateTime::class, true]
                ],
                [
                    ['VIEW', 'Entity:' . \stdClass::class, false],
                    ['VIEW', 'Entity:' . \DateTime::class, false]
                ],
                []
            ],
            'one not protected other granted' => [
                [
                    [\stdClass::class, false],
                    [\DateTime::class, true]
                ],
                [
                    ['VIEW', 'Entity:' . \DateTime::class, true]
                ],
                [\stdClass::class, \DateTime::class]
            ],
            'both protected one granted' => [
                [
                    [\stdClass::class, true],
                    [\DateTime::class, true]
                ],
                [
                    ['VIEW', 'Entity:' . \stdClass::class, false],
                    ['VIEW', 'Entity:' . \DateTime::class, true]
                ],
                [\DateTime::class]
            ],
        ];
    }
}
