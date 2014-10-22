<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\ChangeSet;
use Oro\Bundle\IntegrationBundle\Manager\ChangeSetManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ChangeSetManagerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = '\stdClass';

    /**
     * @var ChangeSetManager
     */
    protected $manager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue(self::CLASS_NAME));

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue(1));

        $this->em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));

        $this->doctrineHelper
            ->expects($this->any())
            ->method('createEntityInstance')
            ->will($this->returnValue(new ChangeSet()));

        $this->repo = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repo));

        $this->manager = new ChangeSetManager($this->doctrineHelper, self::CLASS_NAME);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected one of "localChanges,remoteChanges", "wrong_type" given
     */
    public function testWrongType()
    {
        $this->manager->getChanges(new \stdClass(), 'wrong_type');
    }

    /**
     * @param array  $changeSet
     * @param string $type
     * @param mixed  $expected
     *
     * @dataProvider getChangesDataProvider
     */
    public function testGetChanges($changeSet, $type, $expected)
    {
        if ($changeSet) {
            $this->repo
                ->expects($this->any())
                ->method('findOneBy')
                ->with($this->isType('array'))
                ->will($this->returnValue($changeSet));
        } else {
            $newChangeSet = new ChangeSet();
            $newChangeSet
                ->setEntityClass(self::CLASS_NAME)
                ->setEntityId(1);

            $this->doctrineHelper
                ->expects($this->once())
                ->method('createEntityInstance')
                ->will($this->returnValue($newChangeSet));

            $this->em
                ->expects($this->once())
                ->method('persist')
                ->with($this->equalTo($newChangeSet));
        }

        $this->assertEquals(
            $expected,
            $this->manager->getChanges(new \stdClass(), $type)
        );
    }

    /**
     * @return array
     */
    public function getChangesDataProvider()
    {
        return [
            [
                new ChangeSet(['local'], ['remote']),
                ChangeSet::TYPE_LOCAL,
                ['local'],
            ],
            [
                new ChangeSet(['local'], ['remote']),
                ChangeSet::TYPE_REMOTE,
                ['remote']
            ],
            [
                null,
                ChangeSet::TYPE_REMOTE,
                null
            ]
        ];
    }

    /**
     * @param array  $changeSet
     * @param string $type
     * @param array  $expected
     *
     * @dataProvider setChangesDataProvider
     */
    public function testSetChanges($changeSet, $type, array $expected)
    {
        $propertyAccess = PropertyAccess::createPropertyAccessor();
        $entity         = new \stdClass();
        $this->repo
            ->expects($this->any())
            ->method('findOneBy')
            ->with($this->isType('array'))
            ->will($this->returnValue($changeSet));

        $changeSet = $this->manager->setChanges($entity, $type, $expected);

        $this->assertEquals(
            $expected,
            $propertyAccess->getValue($changeSet, $type)
        );
    }

    /**
     * @return array
     */
    public function setChangesDataProvider()
    {
        return [
            [
                new ChangeSet(['local'], ['remote']),
                ChangeSet::TYPE_LOCAL,
                ['local'],
            ],
            [
                new ChangeSet(['local'], ['remote']),
                ChangeSet::TYPE_REMOTE,
                ['remote']
            ]
        ];
    }

    /**
     * @param array  $changeSet
     * @param string $type
     * @param bool   $removed
     * @param array  $expected
     *
     * @dataProvider removeDataProvider
     */
    public function testRemoveChanges($changeSet, $type, $removed, array $expected)
    {
        $entity = new \stdClass();

        if ($changeSet) {
            $this->repo
                ->expects($this->any())
                ->method('findOneBy')
                ->with($this->isType('array'))
                ->will($this->returnValue($changeSet));
        } else {
            $this->setExpectedException(
                '\InvalidArgumentException',
                'Entity with id 1 not exists'
            );
        }

        if ($removed) {
            $this->em
                ->expects($this->once())
                ->method('remove')
                ->with($this->equalTo($changeSet));
        }

        $changeSet = $this->manager->removeChanges($entity, $type);

        if ($removed) {
            $this->assertNull($changeSet);
        } else {
            list($localChanges, $remoteChanges) = $expected;
            $this->assertEquals($localChanges, $changeSet->getLocalChanges());
            $this->assertEquals($remoteChanges, $changeSet->getRemoteChanges());
        }
    }

    /**
     * @return array
     */
    public function removeDataProvider()
    {
        return [
            [
                new ChangeSet(['local'], []),
                ChangeSet::TYPE_LOCAL,
                true,
                []
            ],
            [
                new ChangeSet([], ['remote']),
                ChangeSet::TYPE_REMOTE,
                true,
                []
            ],
            [
                new ChangeSet(['local'], ['remote']),
                ChangeSet::TYPE_REMOTE,
                false,
                [['local'], null]
            ],
            [
                new ChangeSet(['local'], ['remote']),
                ChangeSet::TYPE_LOCAL,
                false,
                [null, ['remote']]
            ],
            [
                null,
                ChangeSet::TYPE_LOCAL,
                false,
                [null, ['remote']]
            ]
        ];
    }
}
