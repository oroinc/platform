<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EmailBundle\Acl\Voter\EmailTemplateVoter;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EmailTemplateVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EmailTemplateVoter */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new EmailTemplateVoter($this->doctrineHelper);
        $this->voter->setClassName(EmailTemplate::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     *
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes)
    {
        $template = new EmailTemplate();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($template)
            ->will($this->returnValue(EmailTemplate::class));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($template, false)
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            EmailTemplateVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $template, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     *
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedClass(array $attributes)
    {
        $object = new \stdClass();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(\stdClass::class));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            EmailTemplateVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     *
     * @param array $attributes
     */
    public function testGrantedOnExistingNotSystemEmailTemplate(array $attributes)
    {
        $template = new EmailTemplate();
        $template->setIsSystem(false);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($template)
            ->will($this->returnValue(EmailTemplate::class));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($template, false)
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            EmailTemplateVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $template, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     *
     * @param array $attributes
     */
    public function testDeniedOnExistingSystemEmailTemplate(array $attributes)
    {
        $template = new EmailTemplate();
        $template->setIsSystem(true);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($template)
            ->will($this->returnValue(EmailTemplate::class));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($template, false)
            ->willReturn(2);

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            EmailTemplateVoter::ACCESS_DENIED,
            $this->voter->vote($token, $template, $attributes)
        );
    }

    /**
     * @return array
     */
    public function supportedAttributesDataProvider()
    {
        return [
            [['DELETE']],
            [['oro_email_emailtemplate_delete']],
        ];
    }

    /**
     * @return array
     */
    public function unsupportedAttributesDataProvider()
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
