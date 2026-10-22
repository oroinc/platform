<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Acl\Voter\ImportExportResultVoter;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ImportExportResultVoterTest extends TestCase
{
    private MockObject&DoctrineHelper $doctrineHelper;
    private MockObject&AuthorizationCheckerInterface $authorizationChecker;
    private ImportExportResultVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->voter = new ImportExportResultVoter($this->doctrineHelper, $this->authorizationChecker);
        $this->voter->setClassName(ImportExportResult::class);
    }

    public function testVoteAbstainsWhenExportedEntityViewIsGranted(): void
    {
        $result = new ImportExportResult();
        ReflectionUtil::setId($result, 1);
        $result->setEntity('Acme\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($result)
            ->willReturn(1);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(ImportExportResult::class, 1)
            ->willReturn($result);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, 'entity:Acme\Entity')
            ->willReturn(true);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->createMock(TokenInterface::class), $result, [BasicPermission::VIEW])
        );
    }

    public function testVoteDeniesWhenExportedEntityViewIsNotGranted(): void
    {
        $result = new ImportExportResult();
        ReflectionUtil::setId($result, 1);
        $result->setEntity('Acme\Entity');

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($result)
            ->willReturn(1);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(ImportExportResult::class, 1)
            ->willReturn($result);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, 'entity:Acme\Entity')
            ->willReturn(false);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createMock(TokenInterface::class), $result, [BasicPermission::VIEW])
        );
    }

    public function testVoteAbstainsWhenResultIsNotFound(): void
    {
        $result = new ImportExportResult();
        ReflectionUtil::setId($result, 1);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($result)
            ->willReturn(1);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(ImportExportResult::class, 1)
            ->willReturn(null);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->createMock(TokenInterface::class), $result, [BasicPermission::VIEW])
        );
    }

    public function testVoteAbstainsForNotSupportedAttribute(): void
    {
        $result = new ImportExportResult();
        ReflectionUtil::setId($result, 1);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($result)
            ->willReturn(1);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntity');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->createMock(TokenInterface::class), $result, [BasicPermission::EDIT])
        );
    }
}
