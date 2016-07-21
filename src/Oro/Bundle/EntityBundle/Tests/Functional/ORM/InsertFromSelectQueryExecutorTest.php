<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class InsertFromSelectQueryExecutorTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $queryExecutor;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    public function setUp()
    {
        $this->initClient();

        $this->registry = $this->getContainer()->get('doctrine');
        $this->helper = $this->getContainer()->get('oro_entity.orm.native_query_executor_helper');

        $this->queryExecutor = new InsertFromSelectQueryExecutor($this->helper);
    }

    public function testExecute()
    {
        $decimalValue = 12345678.29;

        $group = $this->registry
            ->getManagerForClass('OroUserBundle:Group')
            ->getRepository('OroUserBundle:Group')
            ->findOneBy(['name' => 'Administrators']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect("$decimalValue")
            ->addSelect('(TRUE)')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->innerJoin('u.groups', 'g')
            ->where('u.createdAt <= :datetime')
            ->andWhere('g = :group')
            ->setParameter('datetime', new \DateTime())
            ->setParameter('group', $group)
        ;

        $this->queryExecutor->execute(
            'OroTestFrameworkBundle:Item',
            [
                'stringValue',
                'integerValue',
                'decimalValue',
                'booleanValue',
                'datetimeValue',
                'owner',
                'organization',
            ],
            $queryBuilder
        );

        /** @var User[] $result */
        $users = $this->registry->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')->findAll();

        /** @var Item[] $items */
        $items = $this->registry->getManagerForClass('OroTestFrameworkBundle:Item')
            ->getRepository('OroTestFrameworkBundle:Item')->findAll();

        $this->assertNotEmpty($items);
        $this->assertCount(count($users), $items);

        foreach ($users as $index => $user) {
            $item = $items[$index];
            $this->assertEquals($user->getEmail(), $item->stringValue);
            $this->assertEquals($user->getId(), $item->integerValue);
            $this->assertEquals($decimalValue, $item->decimalValue);
            $this->assertTrue($item->booleanValue);
            $this->assertEquals($user->getCreatedAt(), $item->datetimeValue);
            $this->assertSame($user, $item->owner);
            $this->assertSame($user->getOrganization(), $item->organization);
        }
    }
}
