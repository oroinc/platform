<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\CalendarBundle\Autocomplete\UserCalendarHandler;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;

class UserCalendarHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContextLink;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $treeProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclVoter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var  UserCalendarHandler */
    protected $handler;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContextLink = $this->getMockBuilder(
            'Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new UserCalendarHandler(
            $this->em,
            $this->attachmentManager,
            'Oro\Bundle\CalendarBundle\Autocomplete\UserCalendarHandler',
            $this->securityContextLink,
            $this->treeProvider,
            $this->aclHelper
        );
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler->setEntityNameResolver($this->entityNameResolver);
        $this->handler->setProperties([
            'avatar',
            'firstName',
            'fullName',
            'id',
            'lastName',
            'middleName',
            'namePrefix',
            'nameSuffix',
        ]);
    }

    public function testConvertItem()
    {
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $user->setFirstName('testFirstName');
        $user->setLastName('testLastName');

        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 2);
        $calendar->setOwner($user);

        $result = $this->handler->convertItem($calendar);
        $this->assertEquals($result['id'], $calendar->getId());
        $this->assertEquals($result['userId'], $calendar->getOwner()->getId());
    }
}
