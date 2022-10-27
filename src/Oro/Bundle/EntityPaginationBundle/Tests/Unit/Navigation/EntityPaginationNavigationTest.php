<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Navigation;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Navigation\NavigationResult;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EntityPaginationNavigationTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'id';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var EntityPaginationStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var \stdClass */
    private $entity;

    /** @var EntityPaginationNavigation */
    private $navigation;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->storage = $this->createMock(EntityPaginationStorage::class);
        $this->entity = new \stdClass();

        $this->navigation = new EntityPaginationNavigation(
            $this->doctrineHelper,
            $this->authorizationChecker,
            $this->storage
        );
    }

    /**
     * @dataProvider getTotalCountDataProvider
     */
    public function testGetTotalCount(bool $isValid, bool $inStorage, array $entityIds, ?int $expected)
    {
        $this->assertPrepareNavigation($isValid, $inStorage, $entityIds);
        $result = $this->navigation->getTotalCount($this->entity);

        $this->assertSame($expected, $result);
    }

    private function assertPrepareNavigation(bool $isValid, bool $inStorage, array $entityIds): void
    {
        $this->storage->expects($this->once())
            ->method('isEnvironmentValid')
            ->willReturn($isValid);

        $this->storage->expects($this->any())
            ->method('isEntityInStorage')
            ->willReturn($inStorage);

        $this->storage->expects($this->any())
            ->method('getEntityIds')
            ->willReturn($entityIds);
    }

    public function getTotalCountDataProvider(): array
    {
        return [
            'not valid environment' => [
                'isValid'   => false,
                'inStorage' => true,
                'entityIds' => [1, 2, 3],
                'expected'  => null,
            ],
            'not in storage' => [
                'isValid'   => true,
                'inStorage' => false,
                'entityIds' => [1, 2, 3],
                'expected'  => null,
            ],
            'in storage' => [
                'isValid'   => true,
                'inStorage' => true,
                'entityIds' => [1, 2, 3],
                'expected'  => 3,
            ]
        ];
    }

    /**
     * @dataProvider getCurrentNumberDataProvider
     */
    public function testGetCurrentNumber(
        bool $isValid,
        bool $inStorage,
        int $position,
        array $entityIds,
        ?int $expected
    ) {
        $this->assertPrepareNavigation($isValid, $inStorage, $entityIds);

        $this->storage->expects($this->any())
            ->method('getCurrentPosition')
            ->with($this->entity)
            ->willReturn($position);

        $result = $this->navigation->getCurrentNumber($this->entity);

        $this->assertSame($expected, $result);
    }

    public function getCurrentNumberDataProvider(): array
    {
        return [
            'not valid environment' => [
                'isValid'   => false,
                'inStorage' => true,
                'position'  => 1,
                'entityIds' => [1, 2, 3],
                'expected'  => null,
            ],
            'not in storage' => [
                'isValid'   => true,
                'inStorage' => false,
                'position'  => 1,
                'entityIds' => [1, 2, 3],
                'expected'  => null,
            ],
            'in storage' => [
                'isValid'   => true,
                'inStorage' => true,
                'position'  => 1,
                'entityIds' => [1, 2, 3],
                'expected'  => 2,
            ]
        ];
    }

    /**
     * @dataProvider getFirstIdentifierDataProvider
     */
    public function testGetFirstIdentifier(array $source, array $expected)
    {
        $this->assertPrepareResult(
            $source['isValid'],
            $source['inStorage'],
            $source['isGranted'],
            $source['entityIds']
        );

        $result = $this->navigation->getFirstIdentifier($this->entity);

        $expectedResult = $this->prepareExpectedResult($expected);
        $this->assertSame($expectedResult->getId(), $result->getId());
        $this->assertSame($expectedResult->isAvailable(), $result->isAvailable());
        $this->assertSame($expectedResult->isAccessible(), $result->isAccessible());
    }

    public function getFirstIdentifierDataProvider(): array
    {
        return [
            'valid case' => [
                'source' => [
                    'isValid'  => true,
                    'inStorage'  => true,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3]
                ],
                'expected'   => [
                    'id' => 1,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not enabled' => [
                'source' => [
                    'isValid'  => false,
                    'inStorage'  => true,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3]
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not in storage' => [
                'source' => [
                    'isValid'  => true,
                    'inStorage'  => false,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3]
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ]
        ];
    }

    /**
     * @dataProvider getLastIdentifierDataProvider
     */
    public function testGetLastIdentifier(array $source, array $expected)
    {
        $this->assertPrepareResult(
            $source['isValid'],
            $source['inStorage'],
            $source['isGranted'],
            $source['entityIds']
        );

        $result = $this->navigation->getLastIdentifier($this->entity);
        $expectedResult = $this->prepareExpectedResult($expected);
        $this->assertSame($expectedResult->getId(), $result->getId());
        $this->assertSame($expectedResult->isAvailable(), $result->isAvailable());
        $this->assertSame($expectedResult->isAccessible(), $result->isAccessible());
    }

    public function getLastIdentifierDataProvider(): array
    {
        return [
            'valid case' => [
                'source' => [
                    'isValid'  => true,
                    'inStorage'  => true,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3]
                ],
                'expected'   => [
                    'id' => 3,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not enabled' => [
                'source' => [
                    'isValid'  => false,
                    'inStorage'  => true,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3]
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not in storage' => [
                'source' => [
                    'isValid'  => true,
                    'inStorage'  => false,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3]
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ]
        ];
    }

    /**
     * @dataProvider getPreviousIdentifierDataProvider
     */
    public function testGetPreviousIdentifier(array $source, array $expected)
    {
        $this->assertPrepareResult(
            $source['isValid'],
            $source['inStorage'],
            $source['isGranted'],
            $source['entityIds']
        );

        $this->storage->expects($this->any())
            ->method('getCurrentPosition')
            ->with($this->entity)
            ->willReturn($source['currentPosition']);

        $result = $this->navigation->getPreviousIdentifier($this->entity, $source['scope']);
        $expectedResult = $this->prepareExpectedResult($expected);
        $this->assertSame($expectedResult->getId(), $result->getId());
        $this->assertSame($expectedResult->isAvailable(), $result->isAvailable());
        $this->assertSame($expectedResult->isAccessible(), $result->isAccessible());
    }

    public function getPreviousIdentifierDataProvider(): array
    {
        return [
            'valid case view scope' => [
                'source' => [
                    'isValid'       => true,
                    'inStorage'       => true,
                    'isGranted'       => true,
                    'entityIds'       => [1, 2, 3, 4, 5],
                    'currentPosition' => 3,
                    'scope'           => EntityPaginationManager::VIEW_SCOPE
                ],
                'expected'   => [
                    'id' => 3,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'valid case edit scope' => [
                'source' => [
                    'isValid'       => true,
                    'inStorage'       => true,
                    'isGranted'       => true,
                    'entityIds'       => [1, 2, 3, 4, 5],
                    'currentPosition' => 3,
                    'scope'           => EntityPaginationManager::EDIT_SCOPE
                ],
                'expected'   => [
                    'id' => 3,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not enabled' => [
                'source' => [
                    'isValid'  => false,
                    'inStorage'  => true,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3],
                    'currentPosition' => 3,
                    'scope'           => EntityPaginationManager::VIEW_SCOPE
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not in storage' => [
                'source' => [
                    'isValid'       => true,
                    'inStorage'       => false,
                    'isGranted'       => true,
                    'entityIds'       => [1, 2, 3],
                    'currentPosition' => 3,
                    'scope'           => EntityPaginationManager::VIEW_SCOPE
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ]
        ];
    }
    /**
     * @dataProvider getNextIdentifierDataProvider
     */
    public function testGetNextIdentifier(array $source, array $expected)
    {
        $this->assertPrepareResult(
            $source['isValid'],
            $source['inStorage'],
            $source['isGranted'],
            $source['entityIds']
        );

        $this->storage->expects($this->any())
            ->method('getCurrentPosition')
            ->with($this->entity)
            ->willReturn($source['currentPosition']);

        $result = $this->navigation->getNextIdentifier($this->entity);
        $expectedResult = $this->prepareExpectedResult($expected);
        $this->assertSame($expectedResult->getId(), $result->getId());
        $this->assertSame($expectedResult->isAvailable(), $result->isAvailable());
        $this->assertSame($expectedResult->isAccessible(), $result->isAccessible());
    }

    public function getNextIdentifierDataProvider(): array
    {
        return [
            'valid case' => [
                'source' => [
                    'isValid'       => true,
                    'inStorage'       => true,
                    'isGranted'       => true,
                    'entityIds'       => [1, 2, 3, 4, 5],
                    'currentPosition' => 2
                ],
                'expected'   => [
                    'id' => 4,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not enabled' => [
                'source' => [
                    'isValid'  => false,
                    'inStorage'  => true,
                    'isGranted'  => true,
                    'entityIds'  => [1, 2, 3],
                    'currentPosition' => 3
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ],
            'not in storage' => [
                'source' => [
                    'isValid'       => true,
                    'inStorage'       => false,
                    'isGranted'       => true,
                    'entityIds'       => [1, 2, 3],
                    'currentPosition' => 3
                ],
                'expected'   => [
                    'id' => null,
                    'iaAvailable' => true,
                    'iaAccessible' => true
                ]
            ]
        ];
    }

    private function assertPrepareResult(
        bool $isValid,
        bool $inStorage,
        bool $isGranted,
        array $entityIds,
        int $noEntityId = null
    ): void {
        $this->storage->expects($this->any())
            ->method('isEnvironmentValid')
            ->willReturn($isValid);

        $this->storage->expects($this->any())
            ->method('isEntityInStorage')
            ->willReturn($inStorage);

        if ($noEntityId) {
            $this->doctrineHelper->expects($this->any())
                ->method('getEntity')
                ->willReturn(null);
        } else {
            $this->doctrineHelper->expects($this->any())
                ->method('getEntity')
                ->willReturn($this->entity);
        }

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn($isGranted);

        $this->storage->expects($this->any())
            ->method('getEntityIds')
            ->willReturn($entityIds);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($this->entity)
            ->willReturn(self::FIELD_NAME);
    }

    private function prepareExpectedResult(array $expected): NavigationResult
    {
        $expectedResult = new NavigationResult();
        $expectedResult->setId($expected['id']);
        $expectedResult->setAvailable($expected['iaAvailable']);
        $expectedResult->setAccessible($expected['iaAccessible']);

        return $expectedResult;
    }
}
