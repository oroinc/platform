<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\NoteBundle\Entity\Manager\NoteManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Tests\Unit\Stub\AttachmentProviderStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NoteManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentAssociationHelper;

    /** @var NoteManager */
    protected $manager;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);

        $attachmentProvider = new AttachmentProviderStub(
            $this->em,
            $this->attachmentAssociationHelper,
            $this->attachmentManager
        );

        $this->manager = new NoteManager(
            $this->em,
            $this->authorizationChecker,
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
            ->with($this->identicalTo($qb), 'VIEW', ['checkRelations' => false])
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

        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($note))
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with('DELETE', $this->identicalTo($note))
            ->will($this->returnValue(false));
        $this->authorizationChecker->expects($this->at(2))
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($createdBy))
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->at(3))
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
