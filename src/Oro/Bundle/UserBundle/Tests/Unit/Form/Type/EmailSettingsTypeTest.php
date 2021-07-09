<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;
use Oro\Bundle\UserBundle\Form\Type\EmailSettingsType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class EmailSettingsTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserImapConfigSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $subscriber;

    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $oauthManagerRegistry;

    /** @var EmailSettingsType */
    private $type;

    protected function setUp(): void
    {
        $this->subscriber = $this->createMock(UserImapConfigSubscriber::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);

        $this->type = new EmailSettingsType($this->subscriber, $this->oauthManagerRegistry);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function ($param) {
                $this->assertEquals(User::class, $param['data_class']);
                $this->assertTrue($param['ownership_disabled']);
                $this->assertTrue($param['dynamic_fields_disabled']);

                return true;
            }));
        $this->type->configureOptions($resolver);
    }

    public function testBuildFormImapAccount()
    {
        $this->oauthManagerRegistry->expects($this->once())
            ->method('isOauthImapEnabled')
            ->willReturn(true);

        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'imapAccountType',
                ChoiceAccountType::class,
                [
                    'label' => false,
                    'constraints' => [new Valid()],
                ]
            );

        $this->type->buildForm($builder, []);
    }

    public function testBuildFormImapConfiguration()
    {
        $this->oauthManagerRegistry->expects($this->once())
            ->method('isOauthImapEnabled')
            ->willReturn(false);

        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'imapConfiguration',
                ConfigurationType::class,
                [
                    'label' => false,
                    'constraints' => [new Valid()],
                ]
            );

        $this->type->buildForm($builder, []);
    }
}
