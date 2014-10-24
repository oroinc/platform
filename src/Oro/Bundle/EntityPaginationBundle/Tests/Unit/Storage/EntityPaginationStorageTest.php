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
    protected $configManager;

    /** @var EntityPaginationStorage */
    protected $storage;

    /** @var \stdClass */
    protected $entity;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new EntityPaginationStorage($this->doctrineHelper, $this->configManager);
        $this->entity = new \stdClass();
    }

    /**
     * @param bool $enabled
     * @param bool $request
     * @param array $source
     * @param bool $expected
     *
     * @dataProvider setDataDataProvider
     */
    public function testSetData($enabled, $request, array $source, $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $result = $this->storage->setData(self::ENTITY_NAME, $source['hash'], $source['entity_ids']);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function setDataDataProvider()
    {
        return [
            'is set request' => [
                'enabled'  => true,
                'request'  => true,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => true
            ],
            'is not set request' => [
                'enabled'  => true,
                'request'  => false,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => false
            ],
            'not enabled' => [
                'enabled'  => false,
                'request'  => true,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @param bool $enabled
     * @param bool $request
     * @param string $entityName
     * @param string $hash
     * @param bool $expected
     *
     * @dataProvider hasDataDataProvider
     */
    public function testHasData($enabled, $request, $entityName, $hash, $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $this->storage->setData(self::ENTITY_NAME, self::HASH, []);
        $result = $this->storage->hasData($entityName, $hash);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function hasDataDataProvider()
    {
        return [
            'not enabled' => [
                'enabled' => false,
                'request' => true,
                'entityName' => self::ENTITY_NAME,
                'hash' => self::HASH,
                'expected' => false,
            ],
            'no request' => [
                'enabled' => true,
                'request' => false,
                'entityName' => self::ENTITY_NAME,
                'hash' => self::HASH,
                'expected' => false,
            ],
            'no entity' => [
                'enabled' => true,
                'request' => true,
                'entityName' => 'not existing entity',
                'hash' => 'not existing hash',
                'expected' => false,
            ],
            'no hash' => [
                'enabled' => true,
                'request' => true,
                'entityName' => self::ENTITY_NAME,
                'hash' => 'not existing hash',
                'expected' => false,
            ],
            'data exists' => [
                'enabled' => true,
                'request' => true,
                'entityName' => self::ENTITY_NAME,
                'hash' => self::HASH,
                'expected' => true,
            ],
        ];
    }

    /**
     * @param bool $enabled
     * @param bool $request
     * @param array $entityIds
     * @param mixed $identifier
     * @param mixed $expected
     *
     * @dataProvider getTotalCountDataProvider
     */
    public function testGetTotalCount($enabled, $request, array $entityIds, $identifier, $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $this->storage->setData(self::ENTITY_NAME, self::HASH, $entityIds);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->entity)
            ->will($this->returnValue($identifier));

        $result = $this->storage->getTotalCount($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getTotalCountDataProvider()
    {
        return [
            'not enabled' => [
                'enabled' => false,
                'request' => true,
                'entityIds' => [1, 2, 3],
                'identifier' => 1,
                'expected' => null,
            ],
            'no request' => [
                'enabled' => true,
                'request' => false,
                'entityIds' => [1, 2, 3],
                'identifier' => 1,
                'expected' => null,
            ],
            'empty storage' => [
                'enabled' => true,
                'request' => true,
                'entityIds' => [],
                'identifier' => 4,
                'expected' => null,
            ],
            'not in storage' => [
                'enabled' => true,
                'request' => true,
                'entityIds' => [1, 2, 3],
                'identifier' => 4,
                'expected' => null,
            ],
            'in storage' => [
                'enabled' => true,
                'request' => true,
                'entityIds' => [1, 2, 3],
                'identifier' => 2,
                'expected' => 3,
            ],
        ];
    }

    /**
     * @param bool $enabled
     * @param array $source
     * @param mixed $expected
     *
     * @dataProvider getCurrentNumberDataProvider
     */
    public function testGetCurrentNumber($enabled, array $source, $expected)
    {
        $this->setEnabled($enabled);
        $this->assertStoragePrepare($source);
        $result = $this->storage->getCurrentNumber($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getCurrentNumberDataProvider()
    {
        return [
            'in set' => [
                'enabled'  => true,
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
                'enabled'  => true,
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'not enabled' => [
                'enabled'  => false,
                'source'   => [
                    'entity_id'    => 8,
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
     * @param bool $enabled
     * @param array $source
     * @param mixed $expected
     *
     * @dataProvider getFirstIdentifierDataProvider
     */
    public function testGetFirstIdentifier($enabled, array $source, $expected)
    {
        $this->setEnabled($enabled);
        $this->assertStoragePrepare($source);
        $result = $this->storage->getFirstIdentifier($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getFirstIdentifierDataProvider()
    {
        return [
            'in set' => [
                'enabled'  => true,
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
                'enabled'  => true,
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'not enabled' => [
                'enabled'  => false,
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
        ];
    }

    /**
     * @param bool $enabled
     * @param array $source
     * @param mixed $expected
     *
     * @dataProvider getLastIdentifierDataProvider
     */
    public function testGetLastIdentifier($enabled, array $source, $expected)
    {
        $this->setEnabled($enabled);
        $this->assertStoragePrepare($source);
        $result = $this->storage->getLastIdentifier($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getLastIdentifierDataProvider()
    {
        return [
            'in set' => [
                'enabled'  => true,
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
                'enabled'  => true,
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'enabled' => [
                'enabled'  => false,
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
        ];
    }

    /**
     * @param bool $enabled
     * @param array $source
     * @param mixed $expected
     *
     * @dataProvider getPreviousIdentifierDataProvider
     */
    public function testGetPreviousIdentifier($enabled, array $source, $expected)
    {
        $this->setEnabled($enabled);
        $this->assertStoragePrepare($source);
        $result = $this->storage->getPreviousIdentifier($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getPreviousIdentifierDataProvider()
    {
        return [
            'first in set' => [
                'enabled'  => true,
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
                'enabled'  => true,
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
                'enabled'  => true,
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'not enabled' => [
                'enabled'  => false,
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
        ];
    }
    /**
     * @param bool $enabled
     * @param array $source
     * @param mixed $expected
     *
     * @dataProvider getNextIdentifierDataProvider
     */
    public function testGetNextIdentifier($enabled, array $source, $expected)
    {
        $this->setEnabled($enabled);
        $this->assertStoragePrepare($source);
        $result = $this->storage->getNextIdentifier($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getNextIdentifierDataProvider()
    {
        return [
            'last in set' => [
                'enabled'  => true,
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
                'enabled'  => true,
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
                'enabled'  => true,
                'source'   => [
                    'entity_id'    => 25,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
            'not enabled' => [
                'enabled'  => false,
                'source'   => [
                    'entity_id'    => 5,
                    'storage_data' => [
                        'hash'       => self::HASH,
                        'entity_ids' => self::$entityIds,
                    ]
                ],
                'expected' => null
            ],
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

    public function testGetLimit()
    {
        $limit = 200;

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.limit')
            ->will($this->returnValue($limit));

        $this->assertEquals($limit, $this->storage->getLimit());
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

    /**
     * @param bool $isEnabled
     */
    protected function setEnabled($isEnabled)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_entity_pagination.enabled')
            ->will($this->returnValue($isEnabled));
    }

    protected function setRequest()
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $this->storage->setRequest($request);
    }
}
