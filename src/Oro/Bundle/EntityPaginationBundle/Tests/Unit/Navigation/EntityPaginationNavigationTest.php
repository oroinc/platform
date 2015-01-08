<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Navigation;

use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Navigation\NavigationResult;

class EntityPaginationNavigationTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'stdClass';
    const FIELD_NAME  = 'id';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $storage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $paginationManager;

    /** @var EntityPaginationNavigation */
    protected $navigation;

    /** @var \stdClass */
    protected $entity;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('\Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->navigation = new EntityPaginationNavigation(
            $this->doctrineHelper,
            $this->securityFacade,
            $this->storage
        );
        $this->entity = new \stdClass();
    }

    /**
     * @param bool $isValid
     * @param bool $inStorage
     * @param array $entityIds
     * @param mixed $expected
     *
     * @dataProvider getTotalCountDataProvider
     */
    public function testGetTotalCount($isValid, $inStorage, array $entityIds, $expected)
    {
        $this->assertPrepareNavigation($isValid, $inStorage, $entityIds);
        $result = $this->navigation->getTotalCount($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @param boolean $isValid
     * @param boolean $inStorage
     * @param array $entityIds
     */
    protected function assertPrepareNavigation($isValid, $inStorage, array $entityIds)
    {
        $this->storage->expects($this->once())
            ->method('isEnvironmentValid')
            ->will($this->returnValue($isValid));

        $this->storage->expects($this->any())
            ->method('isEntityInStorage')
            ->will($this->returnValue($inStorage));

        $this->storage->expects($this->any())
            ->method('getEntityIds')
            ->will($this->returnValue($entityIds));
    }

    /**
     * @return array
     */
    public function getTotalCountDataProvider()
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
     * @param boolean $isValid
     * @param boolean $inStorage
     * @param int $position
     * @param array $entityIds
     * @param array $expected
     *
     * @dataProvider getCurrentNumberDataProvider
     */
    public function testGetCurrentNumber($isValid, $inStorage, $position, array $entityIds, $expected)
    {
        $this->assertPrepareNavigation($isValid, $inStorage, $entityIds);

        $this->storage->expects($this->any())
            ->method('getCurrentPosition')
            ->with($this->entity)
            ->will($this->returnValue($position));

        $result = $this->navigation->getCurrentNumber($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getCurrentNumberDataProvider()
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
     * @param array $source
     * @param array $expected
     *
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

    /**
     * @return array
     */
    public function getFirstIdentifierDataProvider()
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
     * @param array $source
     * @param array $expected
     *
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

    /**
     * @return array
     */
    public function getLastIdentifierDataProvider()
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
     * @param array $source
     * @param array $expected
     *
     * @dataProvider getPreviousIdentifierDataProvider
     */
    public function testGetPreviousIdentifier($source, $expected)
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
            ->will($this->returnValue($source['currentPosition']));

        $result = $this->navigation->getPreviousIdentifier($this->entity, $source['scope']);
        $expectedResult = $this->prepareExpectedResult($expected);
        $this->assertSame($expectedResult->getId(), $result->getId());
        $this->assertSame($expectedResult->isAvailable(), $result->isAvailable());
        $this->assertSame($expectedResult->isAccessible(), $result->isAccessible());
    }

    /**
     * @return array
     */
    public function getPreviousIdentifierDataProvider()
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
     * @param array $source
     * @param array $expected
     *
     * @dataProvider getNextIdentifierDataProvider
     */
    public function testGetNextIdentifier($source, $expected)
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
            ->will($this->returnValue($source['currentPosition']));

        $result = $this->navigation->getNextIdentifier($this->entity);
        $expectedResult = $this->prepareExpectedResult($expected);
        $this->assertSame($expectedResult->getId(), $result->getId());
        $this->assertSame($expectedResult->isAvailable(), $result->isAvailable());
        $this->assertSame($expectedResult->isAccessible(), $result->isAccessible());
    }

    /**
     * @return array
     */
    public function getNextIdentifierDataProvider()
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

    /**
     * @param boolean $isValid
     * @param boolean $inStorage
     * @param boolean $isGranted
     * @param array $entityIds
     * @param int|null $noEntityId
     */
    protected function assertPrepareResult($isValid, $inStorage, $isGranted, array $entityIds, $noEntityId = null)
    {
        $this->storage->expects($this->any())
            ->method('isEnvironmentValid')
            ->will($this->returnValue($isValid));

        $this->storage->expects($this->any())
            ->method('isEntityInStorage')
            ->will($this->returnValue($inStorage));

        if ($noEntityId) {
            $this->doctrineHelper->expects($this->any())
                ->method('getEntity')
                ->will($this->returnValue(null));
        } else {
            $this->doctrineHelper->expects($this->any())
                ->method('getEntity')
                ->will($this->returnValue($this->entity));
        }

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue($isGranted));

        $this->storage->expects($this->any())
            ->method('getEntityIds')
            ->will($this->returnValue($entityIds));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($this->entity)
            ->will($this->returnValue(self::FIELD_NAME));
    }

    /**
     * @param array $expected
     * @return NavigationResult
     */
    protected function prepareExpectedResult(array $expected)
    {
        $expectedResult = new NavigationResult();
        $expectedResult->setId($expected['id']);
        $expectedResult->setAvailable($expected['iaAvailable']);
        $expectedResult->setAccessible($expected['iaAccessible']);

        return $expectedResult;
    }
}
