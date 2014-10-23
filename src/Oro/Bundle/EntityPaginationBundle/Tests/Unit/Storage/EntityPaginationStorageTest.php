<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Storage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;

class EntityPaginationStorageTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'stdClass';
    const FIELD_NAME  = 'id';
    const HASH        = '9b59e3bbc14e88a044c112a5b5e914a4';

    public static $entityIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var EntityPaginationStorage */
    protected $storage;

    /** @var \stdClass */
    protected $entity;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_entity_pagination.enabled')
            ->will($this->returnValue(true));

        $this->storage = new EntityPaginationStorage($this->doctrineHelper, $configManager);
        $this->entity = new \stdClass();
    }

    /**
     * @param bool $request
     * @param array $source
     * @param bool $expected
     *
     * @dataProvider addDataDataProvider
     */
    public function testAddData($request, array $source, $expected)
    {
        if (true === $request) {
            $session = new Session(new MockArraySessionStorage());
            $request = new Request();
            $request->setSession($session);
            $this->storage->setRequest($request);
        }

        $result = $this->storage->setData(self::ENTITY_NAME, $source['hash'], $source['entity_ids']);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function addDataDataProvider()
    {
        return [
            'is set request' => [
                'request'  => true,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => true
            ],
            'is not set request' => [
                'request'  => false,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => false
            ]
        ];
    }

    /**
     * @param $source
     * @param $expected
     *
     * @dataProvider getCurrentNumberDataProvider
     */
    public function testGetCurrentNumber($source, $expected)
    {
        $this->assertStoragePrepare($source);
        $result = $this->storage->getCurrentNumber($this->entity);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getCurrentNumberDataProvider()
    {
        return [
            'in set' => [
                'source'   => [
                    'entity_id'    => 8,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => 8
            ],
            'not in set' => [
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ]
        ];
    }

    /**
     * @param $source
     * @param $expected
     *
     * @dataProvider getFirstIdentifierDataProvider
     */
    public function testGetFirstIdentifier($source, $expected)
    {
        $this->assertStoragePrepare($source);
        $result = $this->storage->getFirstIdentifier($this->entity);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getFirstIdentifierDataProvider()
    {
        return [
            'in set' => [
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => 1
            ],
            'not in set' => [
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ]
        ];
    }

    /**
     * @param $source
     * @param $expected
     *
     * @dataProvider getLastIdentifierDataProvider
     */
    public function testGetLastIdentifier($source, $expected)
    {
        $this->assertStoragePrepare($source);
        $result = $this->storage->getLastIdentifier($this->entity);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getLastIdentifierDataProvider()
    {
        return [
            'in set' => [
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => 10
            ],
            'not in set' => [
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ]
        ];
    }

    /**
     * @param $source
     * @param $expected
     *
     * @dataProvider getPreviousIdentifierDataProvider
     */
    public function testGetPreviousIdentifier($source, $expected)
    {
        $this->assertStoragePrepare($source);
        $result = $this->storage->getPreviousIdentifier($this->entity);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getPreviousIdentifierDataProvider()
    {
        return [
            'first in set' => [
                'source'   => [
                    'entity_id'    => 1,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'in set' => [
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => 4
            ],
            'not in set' => [
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ]
        ];
    }
    /**
     * @param $source
     * @param $expected
     *
     * @dataProvider getNextIdentifierDataProvider
     */
    public function testGetNextIdentifier($source, $expected)
    {
        $this->assertStoragePrepare($source);
        $result = $this->storage->getNextIdentifier($this->entity);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getNextIdentifierDataProvider()
    {
        return [
            'last in set' => [
                'source'   => [
                    'entity_id'    => 10,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'in set' => [
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => 6
            ],
            'not in set' => [
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ]
        ];
    }

    /**
     * @param mixed $source
     * @param bool $expected
     * @dataProvider isEnabledDataProvider
     */
    public function testIsEnabled($source, $expected)
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.enabled')
            ->will($this->returnValue($source));

        $storage = new EntityPaginationStorage($this->doctrineHelper, $configManager);
        $this->assertSame($expected, $storage->isEnabled());
    }

    /**
     * @return array
     */
    public function isEnabledDataProvider()
    {
        return [
            'string true' => [
                'source'   => '1',
                'expected' => true,
            ],
            'string false' => [
                'source'   => '0',
                'expected' => false,
            ],
            'boolean true' => [
                'source'   => true,
                'expected' => true,
            ],
            'boolean false' => [
                'source'   => false,
                'expected' => false,
            ],
            'null' => [
                'source'   => null,
                'expected' => false,
            ],
        ];
    }

    /**
     * @param array $source
     */
    protected function assertStoragePrepare(array $source)
    {
        $this->setStorage($source['storage_data']);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->entity)
            ->will($this->returnValue($source['entity_id']));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($this->entity)
            ->will($this->returnValue(self::FIELD_NAME));
    }

    /**
     * @param array $storageData
     */
    protected function setStorage(array $storageData)
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(
            EntityPaginationStorage::STORAGE_NAME,
            [self::ENTITY_NAME => $storageData]
        );
        $request = new Request();
        $request->setSession($session);
        $this->storage->setRequest($request);
    }
}
