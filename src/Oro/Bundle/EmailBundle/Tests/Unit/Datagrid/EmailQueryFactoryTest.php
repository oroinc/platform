<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class EmailQueryFactoryTest extends OrmTestCase
{
    const JOIN_ALIAS              = 'a';
    const TEST_ENTITY             = 'Oro\Bundle\UserBundle\Entity\User';
    const TEST_NAME_DQL_FORMATTED = 'CONCAT(a.firstName, CONCAT(a.lastName, \'\'))';

    /** @var EmailOwnerProviderStorage */
    protected $registry;

    /** @var DQLNameFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var EmailQueryFactory */
    protected $factory;

    public function setUp()
    {
        $this->registry  = new EmailOwnerProviderStorage();
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->factory = new EmailQueryFactory($this->registry, $this->formatter);
    }

    public function tearDown()
    {
        unset($this->factory, $this->formatter, $this->registry);
    }

    public function testPrepareQueryWithoutRroviders()
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')
            ->from('OroEmailBundle:Email', 'e')
            ->leftJoin('e.fromEmailAddress', self::JOIN_ALIAS);

        $this->factory->prepareQuery($qb);
        $this->assertEquals(
            'SELECT e, a.email FROM OroEmailBundle:Email e LEFT JOIN e.fromEmailAddress a',
            $qb->getDQL()
        );
    }

    public function testPrepareQueryOneProviderGiven()
    {
        $provider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $provider->expects($this->any())->method('getEmailOwnerClass')
            ->will($this->returnValue(self::TEST_ENTITY));
        $this->registry->addProvider($provider);

        $this->formatter->expects($this->once())->method('getFormattedNameDQL')
            ->with($this->equalTo('owner1'), $this->equalTo(self::TEST_ENTITY))
            ->will($this->returnValue(self::TEST_NAME_DQL_FORMATTED));
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')
            ->from('OroEmailBundle:Email', 'e')
            ->leftJoin('e.fromEmailAddress', self::JOIN_ALIAS);

        $this->factory->prepareQuery($qb);

        // @codingStandardsIgnoreStart
        $this->assertEquals(
            "SELECT e, " .
            "CONCAT('', CASE WHEN a.hasOwner = true THEN (" .
                "CASE WHEN a.owner1 IS NOT NULL THEN CONCAT(a.firstName, CONCAT(a.lastName, '')) ELSE '' END" .
            ") ELSE a.email END) as fromEmailExpression " .
            "FROM OroEmailBundle:Email e LEFT JOIN e.fromEmailAddress a LEFT JOIN a.owner1 owner1",
            $qb->getDQL()
        );
        // @codingStandardsIgnoreEnd
    }
}
