<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\FieldsChanges;
use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;

class FieldsChangesManagerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = '\stdClass';

    /**
     * @var FieldsChangesManager
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
            ->will($this->returnValue(new FieldsChanges([])));

        $this->repo = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repo));

        $this->manager = new FieldsChangesManager($this->doctrineHelper, self::CLASS_NAME);
    }

    /**
     * @param array $fieldsChanges
     * @param mixed $expected
     * @param bool  $doRemove
     *
     * @dataProvider getChangesDataProvider
     */
    public function testGetChanges($fieldsChanges, $expected, $doRemove)
    {
        if ($fieldsChanges) {
            $this->repo
                ->expects($this->any())
                ->method('findOneBy')
                ->with($this->isType('array'))
                ->will($this->returnValue($fieldsChanges));
        } else {
            $newFieldsChanges = new FieldsChanges([]);
            $newFieldsChanges
                ->setEntityClass(self::CLASS_NAME)
                ->setEntityId(1);

            $this->doctrineHelper
                ->expects($this->once())
                ->method('createEntityInstance')
                ->will($this->returnValue($newFieldsChanges));

            $this->em
                ->expects($this->once())
                ->method('persist')
                ->with($this->equalTo($newFieldsChanges));
        }

        if ($doRemove) {
            $this->em
                ->expects($this->once())
                ->method('remove')
                ->with($this->equalTo($fieldsChanges));
        }

        $this->assertEquals(
            $expected,
            $this->manager->getChanges(new \stdClass(), $doRemove)
        );
    }

    /**
     * @return array
     */
    public function getChangesDataProvider()
    {
        return [
            [new FieldsChanges([]), [], false],
            [new FieldsChanges(['field']), ['field'], false],
            [null, [], false],
            [new FieldsChanges([]), [], true],
            [new FieldsChanges(['field']), ['field'], true],
            [null, [], true]
        ];
    }

    /**
     * @param array $fieldsChanges
     * @param mixed $expected
     *
     * @dataProvider setChangesDataProvider
     */
    public function testSetChanges($fieldsChanges, $expected)
    {
        $entity = new \stdClass();
        $this->repo
            ->expects($this->any())
            ->method('findOneBy')
            ->with($this->isType('array'))
            ->will($this->returnValue($fieldsChanges));

        $fieldsChanges = $this->manager->setChanges($entity, $expected);

        $this->assertEquals(
            $expected,
            $fieldsChanges->getChangedFields()
        );
    }

    /**
     * @return array
     */
    public function setChangesDataProvider()
    {
        return [
            [new FieldsChanges(['field']), ['field']],
            [new FieldsChanges([]), []],
            [null, []],
        ];
    }

    public function testRemoveChanges()
    {
        $entity        = new \stdClass();
        $fieldsChanges = new FieldsChanges([]);

        $this->repo
            ->expects($this->any())
            ->method('findOneBy')
            ->with($this->isType('array'))
            ->will($this->returnValue($fieldsChanges));

        $this->em
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($fieldsChanges));

        $this->manager->removeChanges($entity);
    }
}
