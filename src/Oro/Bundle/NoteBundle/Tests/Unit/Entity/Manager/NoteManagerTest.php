<?php


namespace Oro\Bundle\NoteBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Entity\Manager\NoteManager;
use Oro\Bundle\NoteBundle\Tests\Unit\Stub\AttachmentProviderStub;

class NoteManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentAssociationHelper;

    /** @var NoteManager */
    protected $manager;

    protected function setUp()
    {
        $this->em                 = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade     = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper          = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentManager  = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentAssociationHelper =
            $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $attachmentProvider = new AttachmentProviderStub(
            $this->em,
            $this->attachmentAssociationHelper,
            $this->attachmentManager
        );

        $this->manager = new NoteManager(
            $this->em,
            $this->securityFacade,
            $this->aclHelper,
            $this->entityNameResolver,
            $attachmentProvider,
            $this->attachmentManager
        );
    }

    public function testGetList()
    {
        $entityClass = 'Test\Entity';
        $entityId    = 123;
        $sorting     = 'DESC';
        $result      = ['result'];

        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $repo  = $this->getMockBuilder('Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroNoteBundle:Note')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('getAssociatedNotesQueryBuilder')
            ->with($entityClass, $entityId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('note.createdAt', $sorting)
            ->will($this->returnSelf());
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->identicalTo($qb))
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($result));

        $this->assertEquals(
            $result,
            $this->manager->getList($entityClass, $entityId, $sorting)
        );
    }

    public function testGetEntityViewModels()
    {
        $createdByAvatar = new File();
        $createdBy       = $this->getMockBuilder('Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestUser')
            ->disableOriginalConstructor()
            ->getMock();
        $createdBy->expects($this->once())->method('getId')->will($this->returnValue(100));
        $createdBy->expects($this->once())->method('getAvatar')->will($this->returnValue($createdByAvatar));
        $updatedBy = $this->getMockBuilder('Oro\Bundle\NoteBundle\Tests\Unit\Fixtures\TestUser')
            ->disableOriginalConstructor()
            ->getMock();
        $updatedBy->expects($this->once())->method('getId')->will($this->returnValue(100));
        $updatedBy->expects($this->once())->method('getAvatar')->will($this->returnValue(null));

        $note = new Note();
        $this->setId($note, 123);
        $note
            ->setMessage('test message')
            ->setCreatedAt(new \DateTime('2014-01-20 10:30:40', new \DateTimeZone('UTC')))
            ->setUpdatedAt(new \DateTime('2014-01-21 10:30:40', new \DateTimeZone('UTC')))
            ->setOwner($createdBy)
            ->setUpdatedBy($updatedBy);

        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($note))
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('DELETE', $this->identicalTo($note))
            ->will($this->returnValue(false));
        $this->securityFacade->expects($this->at(2))
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($createdBy))
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->at(3))
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($updatedBy))
            ->will($this->returnValue(false));

        $this->entityNameResolver->expects($this->at(0))
            ->method('getName')
            ->with($this->identicalTo($createdBy))
            ->will($this->returnValue('User1'));
        $this->entityNameResolver->expects($this->at(1))
            ->method('getName')
            ->with($this->identicalTo($updatedBy))
            ->will($this->returnValue('User2'));

        $this->attachmentManager->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($this->identicalTo($createdByAvatar), 'avatar_xsmall')
            ->will($this->returnValue('image1_xsmall'));

        $this->assertEquals(
            [
                [
                    'id'                 => 123,
                    'message'            => 'test message',
                    'createdAt'          => '2014-01-20T10:30:40+00:00',
                    'updatedAt'          => '2014-01-21T10:30:40+00:00',
                    'hasUpdate'          => true,
                    'editable'           => true,
                    'removable'          => false,
                    'createdBy'          => 'User1',
                    'createdBy_id'       => 100,
                    'createdBy_viewable' => true,
                    'createdBy_avatar'   => 'image1_xsmall',
                    'updatedBy'          => 'User2',
                    'updatedBy_id'       => 100,
                    'updatedBy_viewable' => false,
                    'updatedBy_avatar'   => null,
                ]
            ],
            $this->manager->getEntityViewModels([$note])
        );
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
