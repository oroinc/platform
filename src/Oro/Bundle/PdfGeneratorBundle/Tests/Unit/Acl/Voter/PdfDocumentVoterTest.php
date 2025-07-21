<?php

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\Acl\Voter\PdfDocumentVoter;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PdfDocumentVoterTest extends TestCase
{
    private PdfDocumentVoter $voter;

    private MockObject&DoctrineHelper $doctrineHelper;

    private MockObject&ContainerInterface $container;

    private MockObject&AuthorizationCheckerInterface $authorizationChecker;

    private MockObject&TokenInterface $token;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new PdfDocumentVoter($this->doctrineHelper);
        $this->voter->setClassName(PdfDocument::class);
        $this->voter->setContainer($this->container);
    }

    public function testVoteOnValidPdfDocumentWithViewPermissionAccessGranted(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 101);
        $pdfDocument->setSourceEntityClass('Acme\Entity\Sample');
        $pdfDocument->setSourceEntityId(42);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn($pdfDocument->getId());

        $sourceEntity = new \stdClass();
        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntity')
            ->withConsecutive([PdfDocument::class, $pdfDocument->getId()], ['Acme\Entity\Sample', 42])
            ->willReturnOnConsecutiveCalls($pdfDocument, $sourceEntity);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $sourceEntity)
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('oro_security.authorization_checker')
            ->willReturn($this->authorizationChecker);

        // Perform the vote
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteOnPdfDocumentWithNoSourceEntityClassAndIdAccessDenied(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 101);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn($pdfDocument->getId());

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(PdfDocument::class, $pdfDocument->getId())
            ->willReturn($pdfDocument);

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteOnPdfDocumentWithNoSourceEntityClassAccessDenied(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 101);
        $pdfDocument->setSourceEntityClass('Acme\Entity\Sample');

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn($pdfDocument->getId());

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(PdfDocument::class, $pdfDocument->getId())
            ->willReturn($pdfDocument);

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteOnPdfDocumentWithNoSourceEntityIdAccessDenied(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 101);
        $pdfDocument->setSourceEntityClass(42);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn($pdfDocument->getId());

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(PdfDocument::class, $pdfDocument->getId())
            ->willReturn($pdfDocument);

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteOnPdfDocumentWithSourceEntityNotFoundAccessDenied(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 101);
        $pdfDocument->setSourceEntityClass('Acme\Entity\Sample');
        $pdfDocument->setSourceEntityId(42);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn($pdfDocument->getId());

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntity')
            ->withConsecutive([PdfDocument::class, $pdfDocument->getId()], ['Acme\Entity\Sample', 42])
            ->willReturnOnConsecutiveCalls($pdfDocument, null);

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteOnPdfDocumentWithUnsupportedAttributeAccessAbstain(): void
    {
        $pdfDocument = new PdfDocument();
        ReflectionUtil::setId($pdfDocument, 101);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn($pdfDocument->getId());

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntity');

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote with an unsupported attribute
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::EDIT]);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteOnPdfDocumentWithoutIdTokenAccessAbstain(): void
    {
        $pdfDocument = new PdfDocument();

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($pdfDocument)
            ->willReturn(null);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntity');

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote with new PDF document
        $result = $this->voter->vote($this->token, $pdfDocument, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteOnNullObjectAccessAbstain(): void
    {
        $this->doctrineHelper
            ->expects(self::never())
            ->method('getSingleEntityIdentifier');

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntity');

        $this->container
            ->expects(self::never())
            ->method('get');

        // Perform the vote with null object
        $result = $this->voter->vote($this->token, null, [BasicPermission::VIEW]);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
