<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider\Filter;

use Doctrine\Tests\Common\Persistence\ObjectManagerDecoratorTest;
use Oro\Bundle\UserBundle\Provider\Filter\ChoiceTreeUserProvider;

class ChoiceTreeUserProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChoiceTreeUserProvider */
    protected $choiceTreeUserProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceTreeUserProvider = new ChoiceTreeUserProvider(
            $this->registry,
            $this->aclHelper
        );
    }

    public function testGetList()
    {
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())->method('getRepository')->willReturn($repository);
        $this->registry->expects($this->once())->method('getManager')->willReturn($manager);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->any())->method('getResult')->willReturn($this->getUsers());
        $this->aclHelper->expects($this->any())->method('apply')->willReturn($qb);

        $result = $this->choiceTreeUserProvider->getList();
        $this->assertEquals($this->getExpectedData(), $result);
    }

    public function testGetEmptyList()
    {
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())->method('getRepository')->willReturn($repository);
        $this->registry->expects($this->once())->method('getManager')->willReturn($manager);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->any())->method('getResult')->willReturn([]);
        $this->aclHelper->expects($this->any())->method('apply')->willReturn($qb);

        $result = $this->choiceTreeUserProvider->getList();
        $this->assertEquals([], $result);
    }

    public function getUsers()
    {
        $data = $this->getExpectedData();

        $response = [];

        foreach($data as $item) {
            $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();

            $user->expects($this->any())->method('getId')->willReturn($item['id']);
            $user->expects($this->any())->method('getFullName')->willReturn($item['name']);

            $response[] = $user;
        }

        return $response;
    }

    protected function getExpectedData()
    {
        return [
            [
                'name' => 'user 1',
                'id' => 1,
            ],
            [
                'name' => 'user 2',
                'id' => '2',
            ]
        ];
    }
}
