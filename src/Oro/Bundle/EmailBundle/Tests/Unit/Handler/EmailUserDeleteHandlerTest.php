<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Handler\EmailUserDeleteHandler;
use Oro\Bundle\EmailBundle\Handler\EmailUserDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EmailUserDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailUserDeleteHandler */
    private $deleteHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(EmailUser::class)
            ->willReturn($this->em);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new EmailUserDeleteHandlerExtension();
        $extension->setDoctrine($doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects(self::any())
            ->method('getHandlerExtension')
            ->with(EmailUser::class)
            ->willReturn($extension);

        $this->deleteHandler = new EmailUserDeleteHandler();
        $this->deleteHandler->setExtensionRegistry($extensionRegistry);
        $this->deleteHandler->setDoctrine($doctrine);
        $this->deleteHandler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
    }

    private function getEmailUser(int $id): EmailUser
    {
        $emailUser = new EmailUser();
        ReflectionUtil::setId($emailUser, $id);

        return $emailUser;
    }

    private function setEmail(array $emailUsers): void
    {
        $email = new Email();
        /** @var EmailUser $emailUser */
        foreach ($emailUsers as $emailUser) {
            $email->addEmailUser($emailUser);
            $emailUser->setEmail($email);
        }
    }

    public function testIsDeleteGrantedWhenEmailUserDoNotBelongToEmail(): void
    {
        $emailUser = $this->getEmailUser(1);

        self::assertTrue($this->deleteHandler->isDeleteGranted($emailUser));
    }

    public function testIsDeleteGrantedWhenEmailHasOtherEmailUser(): void
    {
        $emailUser1 = $this->getEmailUser(1);
        $emailUser2 = $this->getEmailUser(2);
        $this->setEmail([$emailUser1, $emailUser2]);

        self::assertTrue($this->deleteHandler->isDeleteGranted($emailUser1));
    }

    public function testIsDeleteGrantedForLastEmailUser(): void
    {
        $emailUser = $this->getEmailUser(1);
        $this->setEmail([$emailUser]);

        self::assertFalse($this->deleteHandler->isDeleteGranted($emailUser));
    }

    public function testHandleDeleteWhenEmailUserDoNotBelongToEmail(): void
    {
        $emailUser = $this->getEmailUser(1);

        $this->em->expects(self::once())
            ->method('remove')
            ->with($emailUser);

        $this->em->expects(self::once())
            ->method('flush');

        $this->deleteHandler->delete($emailUser);
    }

    public function testHandleDeleteWhenEmailHasOtherEmailUser(): void
    {
        $emailUser1 = $this->getEmailUser(1);
        $emailUser2 = $this->getEmailUser(2);
        $this->setEmail([$emailUser1, $emailUser2]);

        $this->em->expects(self::once())
            ->method('remove')
            ->with($emailUser1);

        $this->em->expects(self::once())
            ->method('flush');

        $this->deleteHandler->delete($emailUser1);
    }

    public function testHandleDeleteForLastEmailUser(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(
            'The delete operation is forbidden. Reason: an email should have at least one email user.'
        );

        $emailUser = $this->getEmailUser(1);
        $this->setEmail([$emailUser]);

        $this->em->expects(self::never())
            ->method('remove');

        $this->em->expects(self::never())
            ->method('flush');

        $this->deleteHandler->delete($emailUser);
    }

    public function testHandleDeleteAllEmailUsers(): void
    {
        $emailUser1 = $this->getEmailUser(1);
        $emailUser2 = $this->getEmailUser(2);
        $this->setEmail([$emailUser1, $emailUser2]);

        $this->em->expects(self::once())
            ->method('remove')
            ->with($emailUser1);

        $this->em->expects(self::once())
            ->method('flush');

        $this->deleteHandler->delete($emailUser1);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(
            'The delete operation is forbidden. Reason: an email should have at least one email user.'
        );
        $this->deleteHandler->delete($emailUser2);
    }
}
