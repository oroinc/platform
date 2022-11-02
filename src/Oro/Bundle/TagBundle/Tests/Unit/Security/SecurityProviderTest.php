<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Security;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Security\SecurityProvider as SearchSecurityProvider;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

class SecurityProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider aclDataProvider
     */
    public function testApplyAcl(array $protectedMap, array $grantedMap, array $expectedAllowedEntities)
    {
        $entities = [
            ['entityName' => '\stdClass'],
            ['entityName' => '\DateTime']
        ];
        $tableAlias = 'alias';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($entities);

        $searchQb = $this->createMock(QueryBuilder::class);
        $searchQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $searchQb->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($searchQb);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn(new Expr());
        if ($expectedAllowedEntities) {
            if (count($expectedAllowedEntities) !== count($entities)) {
                $qb->expects($this->once())
                    ->method('andWhere')
                    ->with($tableAlias . '.entityName IN(:allowedEntities)')
                    ->willReturnSelf();
                $qb->expects($this->once())
                    ->method('setParameter')
                    ->with('allowedEntities', $expectedAllowedEntities)
                    ->willReturnSelf();
            }
        } else {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with('1 = 0')
                ->willReturnSelf();
        }

        $searchSecurityProvider = $this->createMock(SearchSecurityProvider::class);
        $searchSecurityProvider->expects($this->exactly(count($entities)))
            ->method('isProtectedEntity')
            ->willReturnMap($protectedMap);

        if ($grantedMap) {
            $searchSecurityProvider->expects($this->exactly(count($grantedMap)))
                ->method('isGranted')
                ->willReturnMap($grantedMap);
        } else {
            $searchSecurityProvider->expects($this->never())
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
                ['\stdClass', '\DateTime']
            ],
            'protected and granted' => [
                [
                    ['\stdClass', true],
                    ['\DateTime', true]
                ],
                [
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\stdClass', true],
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', true]
                ],
                ['\stdClass', '\DateTime']
            ],
            'protected not granted' => [
                [
                    ['\stdClass', true],
                    ['\DateTime', true]
                ],
                [
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\stdClass', false],
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', false]
                ],
                []
            ],
            'one not protected other granted' => [
                [
                    ['\stdClass', false],
                    ['\DateTime', true]
                ],
                [
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', true]
                ],
                ['\stdClass', '\DateTime']
            ],
            'both protected one granted' => [
                [
                    ['\stdClass', true],
                    ['\DateTime', true]
                ],
                [
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\stdClass', false],
                    [SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', true]
                ],
                ['\DateTime']
            ],
        ];
    }
}
