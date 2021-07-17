<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\Manager;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeDataForScopeManagerTests as LoadScopeData;
use Oro\Bundle\ScopeBundle\Tests\Functional\Stub\StubContext;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ScopeManagerTest extends WebTestCase
{
    private const TEST_SCOPE = 'test_scope';

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadScopeData::class]);
        $this->getContainer()->get('security.token_storage')->setToken(new ImpersonationToken(
            $this->getReference(LoadScopeData::USER),
            $this->getReference(LoadScopeData::ORGANIZATION)
        ));
    }

    private function getScopeManager(): ScopeManager
    {
        return $this->getContainer()->get('oro_scope.scope_manager');
    }

    private function getScope(string $reference): Scope
    {
        return $this->getReference($reference);
    }

    public function testFindDefaultScope()
    {
        $scope = $this->getScopeManager()->findDefaultScope();
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
    }

    public function testFindWhenScopeDoesNotExist()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context);
        $this->assertTrue(null === $scope);
        $this->assertNull($this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindScheduled()
    {
        $context = [
            'organization' => $this->getReference(LoadScopeData::ORGANIZATION),
            'user'         => $this->getReference(LoadScopeData::USER1)
        ];

        // guard
        $this->assertTrue(null === $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context));

        $scheduledScope = $this->getScopeManager()->createScopeByCriteria(
            $this->getScopeManager()->getCriteria(ScopeManager::BASE_SCOPE, $context),
            false
        );
        $this->assertSame(
            $scheduledScope,
            $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context)
        );
        $this->assertNull($this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindForObjectContextForBaseScope()
    {
        $context = new StubContext();
        $context->setOrganization(null);
        $context->setUser($this->getReference(LoadScopeData::USER));
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindForObjectContextForTestScope()
    {
        $context = new StubContext();
        $context->setOrganization(null);
        $context->setUser($this->getReference(LoadScopeData::USER));
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE, $context));
    }

    public function testFindForNullContextForBaseScope()
    {
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE));
    }

    public function testFindForNullContextForTestScope()
    {
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE);
        $this->assertSame($this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE));
    }

    public function testFindForEmptyContextForBaseScope()
    {
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, []);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, []));
    }

    public function testFindForEmptyContextForTestScope()
    {
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE, []);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE, []));
    }

    public function testFindForOneAssociationForBaseScope()
    {
        $context = ['organization' => $this->getReference(LoadScopeData::ORGANIZATION)];
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindForOneAssociationForTestScope()
    {
        $context = ['organization' => $this->getReference(LoadScopeData::ORGANIZATION)];
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE, $context));
    }

    public function testFindForSeveralAssociationForBaseScope()
    {
        $context = [
            'organization' => $this->getReference(LoadScopeData::ORGANIZATION),
            'user'         => $this->getReference(LoadScopeData::USER)
        ];
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindForSeveralAssociationForTestScope()
    {
        $context = [
            'organization' => $this->getReference(LoadScopeData::ORGANIZATION),
            'user'         => $this->getReference(LoadScopeData::USER)
        ];
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE, $context));
    }

    public function testFindForNullValueInContextForBaseScope()
    {
        $context = [
            'organization' => null,
            'user'         => $this->getReference(LoadScopeData::USER)
        ];
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindForNullValueInContextForTestScope()
    {
        $context = [
            'organization' => null,
            'user'         => $this->getReference(LoadScopeData::USER)
        ];
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE, $context));
    }

    public function testFindForIsNotNullValueInContextForBaseScope()
    {
        $context = [
            'organization' => ScopeCriteria::IS_NOT_NULL,
            'user'         => $this->getReference(LoadScopeData::USER)
        ];
        $scope = $this->getScopeManager()->find(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(ScopeManager::BASE_SCOPE, $context));
    }

    public function testFindForIsNotNullValueInContextForTestScope()
    {
        $context = [
            'organization' => ScopeCriteria::IS_NOT_NULL,
            'user'         => $this->getReference(LoadScopeData::USER)
        ];
        $scope = $this->getScopeManager()->find(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(), $scope->getId());
        $this->assertSame($scope->getId(), $this->getScopeManager()->findId(self::TEST_SCOPE, $context));
    }

    public function testFindByForNullContextForBaseScope()
    {
        $scopes = $this->getScopeManager()->findBy(ScopeManager::BASE_SCOPE);
        $this->assertSame(
            [$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForNullContextForTestScope()
    {
        $scopes = $this->getScopeManager()->findBy(self::TEST_SCOPE);
        $this->assertSame(
            [$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForEmptyContextForBaseScope()
    {
        $scopes = $this->getScopeManager()->findBy(ScopeManager::BASE_SCOPE, []);
        $this->assertSame(
            [$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForEmptyContextForTestScope()
    {
        $scopes = $this->getScopeManager()->findBy(self::TEST_SCOPE, []);
        $this->assertSame(
            [$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForContentForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $scopes = $this->getScopeManager()->findBy(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame(
            [$this->getScope(LoadScopeData::USER_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForContentForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $scopes = $this->getScopeManager()->findBy(self::TEST_SCOPE, $context);
        $this->assertSame(
            [$this->getScope(LoadScopeData::USER_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForNotMatchedContentForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scopes = $this->getScopeManager()->findBy(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame(
            [],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindByForNotMatchedContentForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scopes = $this->getScopeManager()->findBy(self::TEST_SCOPE, $context);
        $this->assertSame(
            [],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForNullContextForBaseScope()
    {
        $scopes = $this->getScopeManager()->findRelatedScopes(ScopeManager::BASE_SCOPE);
        $this->assertSame(
            [$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForNullContextForTestScope()
    {
        $scopes = $this->getScopeManager()->findRelatedScopes(self::TEST_SCOPE);
        $this->assertSame(
            [$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForEmptyContextForBaseScope()
    {
        $scopes = $this->getScopeManager()->findRelatedScopes(ScopeManager::BASE_SCOPE, []);
        $this->assertSame(
            [$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForEmptyContextForTestScope()
    {
        $scopes = $this->getScopeManager()->findRelatedScopes(self::TEST_SCOPE, []);
        $this->assertSame(
            [$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForContextForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $scopes = $this->getScopeManager()->findRelatedScopes(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame(
            [$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $scopes = $this->getScopeManager()->findRelatedScopes(self::TEST_SCOPE, $context);
        $this->assertSame(
            [$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopesForNotMatchedContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER1)];
        $scopes = $this->getScopeManager()->findRelatedScopes(self::TEST_SCOPE, $context);
        $this->assertSame(
            [],
            array_map(
                function (Scope $scope) {
                    return $scope->getId();
                },
                iterator_to_array($scopes)
            )
        );
    }

    public function testFindRelatedScopeIdsForNullContextForBaseScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIds(ScopeManager::BASE_SCOPE);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsForNullContextForTestScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIds(self::TEST_SCOPE);
        $this->assertSame([$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsForEmptyContextForBaseScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIds(ScopeManager::BASE_SCOPE, []);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsForEmptyContextForTestScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIds(self::TEST_SCOPE, []);
        $this->assertSame([$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsForContextForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $ids = $this->getScopeManager()->findRelatedScopeIds(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsForContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $ids = $this->getScopeManager()->findRelatedScopeIds(self::TEST_SCOPE, $context);
        $this->assertSame([$this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsForNotMatchedContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER1)];
        $ids = $this->getScopeManager()->findRelatedScopeIds(self::TEST_SCOPE, $context);
        $this->assertSame([], $ids);
    }

    public function testFindRelatedScopeIdsWithPriorityForNullContextForBaseScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(ScopeManager::BASE_SCOPE);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsWithPriorityForNullContextForTestScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(self::TEST_SCOPE);
        $this->assertSame(
            [
                $this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(),
                $this->getScope(LoadScopeData::ORGANIZATION_SCOPE)->getId(),
                $this->getScope(LoadScopeData::USER_SCOPE)->getId(),
                $this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()
            ],
            $ids
        );
    }

    public function testFindRelatedScopeIdsWithPriorityForEmptyContextForBaseScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(ScopeManager::BASE_SCOPE, []);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsWithPriorityForEmptyContextForTestScope()
    {
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(self::TEST_SCOPE, []);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsWithPriorityForContextForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame(
            [
                $this->getScope(LoadScopeData::USER_SCOPE)->getId(),
                $this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()
            ],
            $ids
        );
    }

    public function testFindRelatedScopeIdsWithPriorityForContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(self::TEST_SCOPE, $context);
        $this->assertSame(
            [
                $this->getScope(LoadScopeData::USER_SCOPE)->getId(),
                $this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()
            ],
            $ids
        );
    }

    public function testFindRelatedScopeIdsWithPriorityForNotMatchedContextForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindRelatedScopeIdsWithPriorityForNotMatchedContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $ids = $this->getScopeManager()->findRelatedScopeIdsWithPriority(self::TEST_SCOPE, $context);
        $this->assertSame([$this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId()], $ids);
    }

    public function testFindMostSuitableForNullContextForBaseScope()
    {
        $scope = $this->getScopeManager()->findMostSuitable(ScopeManager::BASE_SCOPE);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForNullContextForTestScope()
    {
        $scope = $this->getScopeManager()->findMostSuitable(self::TEST_SCOPE);
        $this->assertSame($this->getScope(LoadScopeData::USER_ORGANIZATION_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForEmptyContextForBaseScope()
    {
        $scope = $this->getScopeManager()->findMostSuitable(ScopeManager::BASE_SCOPE, []);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForEmptyContextForTestScope()
    {
        $scope = $this->getScopeManager()->findMostSuitable(self::TEST_SCOPE, []);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForContextForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $scope = $this->getScopeManager()->findMostSuitable(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER)];
        $scope = $this->getScopeManager()->findMostSuitable(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForNotMatchedContextForBaseScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scope = $this->getScopeManager()->findMostSuitable(ScopeManager::BASE_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
    }

    public function testFindMostSuitableForNotMatchedContextForTestScope()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scope = $this->getScopeManager()->findMostSuitable(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::DEFAULT_SCOPE)->getId(), $scope->getId());
    }

    public function testFindOrCreateForExisting()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER1)];
        $scope = $this->getScopeManager()->findOrCreate(self::TEST_SCOPE, $context);
        $this->assertSame($this->getScope(LoadScopeData::USER1_SCOPE)->getId(), $scope->getId());
    }

    public function testFindOrCreateForNotExistingWithFlush()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scope = $this->getScopeManager()->findOrCreate(self::TEST_SCOPE, $context);
        $this->assertInstanceOf(Scope::class, $scope);
        $this->assertNotNull($scope->getId());
    }

    public function testFindOrCreateForNotExistingWithoutFlush()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scope = $this->getScopeManager()->findOrCreate(self::TEST_SCOPE, $context, false);
        $this->assertInstanceOf(Scope::class, $scope);
        $this->assertNull($scope->getId());
    }

    public function testFindOrCreateForNotExistingWithoutFlushWhenScopeCreationAlreadyScheduled()
    {
        $context = ['user' => $this->getReference(LoadScopeData::USER2)];
        $scope1 = $this->getScopeManager()->findOrCreate(self::TEST_SCOPE, $context, false);
        $this->assertInstanceOf(Scope::class, $scope1);
        $this->assertNull($scope1->getId());
        $scope2 = $this->getScopeManager()->findOrCreate(self::TEST_SCOPE, $context, false);
        $this->assertInstanceOf(Scope::class, $scope2);
        $this->assertNull($scope2->getId());
        $this->assertSame($scope1, $scope2);
    }
}
