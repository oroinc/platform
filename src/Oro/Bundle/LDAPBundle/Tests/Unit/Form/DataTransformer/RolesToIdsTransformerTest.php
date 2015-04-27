<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LDAPBundle\Form\DataTransformer\RolesToIdsTransformer;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingRole;

class RolesToIdsTransformerTest extends \PHPUnit_Framework_TestCase
{
    private $em;

    private $transformer;

    public function setUp()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with('Oro\Bundle\UserBundle\Entity\Role')
            ->will($this->returnValue($metadata));

        $this->transformer = new RolesToIdsTransformer($this->em, 'Oro\Bundle\UserBundle\Entity\Role');
    }

    public function testTransform()
    {
        $data = [
            1,
            2,
        ];

        $expected = new ArrayCollection(
            [
                new TestingRole('role1', 1),
                new TestingRole('role2', 2),
            ]
        );
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();

        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($expected->toArray()));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $queryBuilder->expects($this->any())
            ->method('where')
            ->will($this->returnValue($queryBuilder));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('Oro\Bundle\UserBundle\Entity\Role')
            ->will($this->returnValue($repository));

        $actual = $this->transformer->transform($data);

        $this->assertEquals($expected, $actual);
    }

    public function testReverseTransform()
    {
        $data = new ArrayCollection(
            [
                new TestingRole('role1', 1),
                new TestingRole('role2', 2),
            ]
        );

        $expected = [
            1,
            2,
        ];
        $actual = $this->transformer->reverseTransform($data);

        $this->assertEquals($expected, $actual);
    }
}
