<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQuery;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\WebTestCase;

/**
 * @dbIsolation
 */
class InsertFromSelectQueryTest extends WebTestCase
{
    /**
     * @var InsertFromSelectQuery
     */
    protected $helper;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\TestFrameworkBundle\Fixtures\LoadUserData'
        ]);

        $this->helper = new InsertFromSelectQuery($this->getContainer()->get('doctrine'));
    }

    public function testExecute()
    {
        $register = $this->getContainer()->get('doctrine');

        $decimalValue = 12345678.29;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $register
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect("$decimalValue")
            ->addSelect('u.id')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->where('u.createdAt < :datetime')
            ->setParameter('datetime', new \DateTime())
        ;

        $this->helper->execute(
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
        $users = $register->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')->findAll();

        /** @var Item[] $items */
        $items = $register->getManagerForClass('OroTestFrameworkBundle:Item')
            ->getRepository('OroTestFrameworkBundle:Item')->findAll();

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
