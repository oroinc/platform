<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EmailBundle\Acl\Voter\EmailTemplateVoter;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EmailTemplateVoterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EmailTemplateVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new EmailTemplateVoter($this->doctrineHelper);
        $this->voter->setClassName(EmailTemplate::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes): void
    {
        $template = new EmailTemplate();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($template, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $template, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedClass(array $attributes): void
    {
        $object = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testGrantedOnExistingNotSystemEmailTemplate(array $attributes): void
    {
        $template = new EmailTemplate();
        $template->setIsSystem(false);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($template, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $template, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testDeniedOnExistingSystemEmailTemplate(array $attributes): void
    {
        $template = new EmailTemplate();
        $template->setIsSystem(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($template, false)
            ->willReturn(2);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $template, $attributes)
        );
    }

    public function supportedAttributesDataProvider(): array
    {
        return [
            [['DELETE']],
            [['oro_email_emailtemplate_delete']],
        ];
    }

    public function unsupportedAttributesDataProvider(): array
    {
        return [
            [['VIEW']],
            [['EDIT']],
            [['CREATE']],
            [['oro_email_emailtemplate_index']],
            [['oro_email_emailtemplate_view']],
        ];
    }
}
