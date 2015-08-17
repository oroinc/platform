<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class EmailQueryFactoryTest extends OrmTestCase
{
    const JOIN_ALIAS              = 'a';
    const TEST_ENTITY             = 'Oro\Bundle\UserBundle\Entity\User';
    const TEST_NAME_DQL_FORMATTED = 'CONCAT(a.firstName, CONCAT(a.lastName, \'\'))';

    /** @var EmailOwnerProviderStorage */
    protected $providerStorage;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var EmailQueryFactory */
    protected $factory;

    /** @var Registry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var MailboxRepository */
    protected $mailboxRepository;

    public function setUp()
    {
        $this->providerStorage  = new EmailOwnerProviderStorage();

        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()->getMock();

        $this->mailboxRepository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroEmailBundle:Mailbox'))
            ->will($this->returnValue($this->mailboxRepository));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new EmailQueryFactory(
            $this->providerStorage,
            $this->entityNameResolver,
            $this->doctrine,
            $this->securityFacade
        );
    }

    public function tearDown()
    {
        unset(
            $this->factory,
            $this->entityNameResolver,
            $this->providerStorage,
            $this->mailboxRepository,
            $this->doctrine
        );
    }

    public function testPrepareQueryWithoutProviders()
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
        $this->providerStorage->addProvider($provider);

        $this->entityNameResolver->expects($this->once())->method('getNameDQL')
            ->with(self::TEST_ENTITY, 'owner1')
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

    public function testFilterQueryByUserIdWhenMailboxesAreFound()
    {
        $user = new User();
        $organization = new Organization();

        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder();

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->mailboxRepository->expects($this->any())
            ->method('findAvailableMailboxIds')
            ->with($user, $organization)
            ->will($this->returnValue([1, 3, 5]));

        $qb->select('eu')
            ->from('EmailUser', 'eu');

        $this->factory->applyAcl($qb, 1);

        $this->assertEquals(
            "SELECT eu FROM EmailUser eu" .
            " WHERE (eu.owner = :owner AND eu.organization  = :organization) OR eu.mailboxOwner IN(:mailboxIds)",
            $qb->getQuery()->getDQL()
        );
    }

    public function testFilterQueryByUserIdWhenNoMailboxesFound()
    {
        $user = new User();
        $organization = new Organization();

        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder();

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->mailboxRepository->expects($this->any())
            ->method('findAvailableMailboxIds')
            ->with($user, $organization)
            ->will($this->returnValue([1, 3, 5]));

        $qb->select('eu')
            ->from('EmailUser', 'eu');

        $this->factory->applyAcl($qb, 1);

        $this->assertEquals(
            "SELECT eu FROM EmailUser eu" .
            " WHERE (eu.owner = :owner AND eu.organization  = :organization) OR eu.mailboxOwner IN(:mailboxIds)",
            $qb->getQuery()->getDQL()
        );
    }
}
