<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\FormFactoryInterface;

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

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var MailboxManager */
    protected $mailboxManager;

    public function setUp()
    {
        $this->providerStorage  = new EmailOwnerProviderStorage();

        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()->getMock();

        $this->mailboxManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        /** @var FormFactoryInterface $formFactory */
        $formFactory   = $this->getMockForAbstractClass(FormFactoryInterface::class);

        $filterUtility = new FilterUtility();

        $this->factory = new EmailQueryFactory(
            $this->providerStorage,
            $this->entityNameResolver,
            $this->mailboxManager,
            $this->tokenAccessor,
            $formFactory,
            $filterUtility
        );
    }

    public function tearDown()
    {
        unset(
            $this->factory,
            $this->entityNameResolver,
            $this->providerStorage,
            $this->mailboxManager,
            $this->doctrine
        );
    }

    public function testAddFromEmailAddressWithoutProviders()
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')
            ->from('OroEmailBundle:Email', 'e')
            ->leftJoin('e.fromEmailAddress', self::JOIN_ALIAS);

        $this->factory->addFromEmailAddress($qb);
        $this->assertEquals(
            'SELECT e, NULLIF(\'\', \'\') AS fromEmailAddressOwnerClass,'
            . ' NULLIF(0, 0) AS fromEmailAddressOwnerId, a.email AS fromEmailAddress'
            . ' FROM OroEmailBundle:Email e LEFT JOIN e.fromEmailAddress a',
            $qb->getDQL()
        );
    }

    public function testAddFromEmailAddressOneProviderGiven()
    {
        $provider = $this->createMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
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

        $this->factory->addFromEmailAddress($qb);

        // @codingStandardsIgnoreStart
        $this->assertEquals(
            'SELECT e,'
            . ' (CASE'
            . ' WHEN a.owner1 IS NOT NULL THEN \'Oro\Bundle\UserBundle\Entity\User\''
            . ' ELSE NULLIF(\'\', \'\') END) AS fromEmailAddressOwnerClass,'
            . ' COALESCE(IDENTITY(a.owner1) ) AS fromEmailAddressOwnerId,'
            . ' CONCAT(\'\','
            . ' CASE WHEN a.hasOwner = true THEN (CASE'
            . ' WHEN a.owner1 IS NOT NULL THEN CONCAT(a.firstName, CONCAT(a.lastName, \'\'))'
            . ' ELSE \'\' END) ELSE a.email END) AS fromEmailAddress'
            . ' FROM OroEmailBundle:Email e LEFT JOIN e.fromEmailAddress a LEFT JOIN a.owner1 owner1',
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

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->mailboxManager->expects($this->any())
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

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->mailboxManager->expects($this->any())
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
