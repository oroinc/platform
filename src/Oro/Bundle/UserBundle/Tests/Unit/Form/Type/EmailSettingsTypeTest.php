<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\ImapBundle\Form\Type\ChoiceAccountType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;
use Oro\Bundle\UserBundle\Form\Type\EmailSettingsType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\Valid;

class EmailSettingsTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailSettingsType
     */
    protected $type;

    /** @var MockObject|OAuth2ManagerRegistry */
    protected $registry;

    /** @var MockObject|UserImapConfigSubscriber */
    protected $subscriber;

    /**
     * Setup test env
     */
    protected function setUp(): void
    {
        $this->createRegistryMock();
        $this->subscriber = $this->getMockBuilder(UserImapConfigSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new EmailSettingsType($this->subscriber, $this->registry);
    }

    protected function createRegistryMock(): void
    {
        $this->registry = $this->getMockBuilder(OAuth2ManagerRegistry::class)->getMock();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function ($param) {
                $this->assertEquals($param['data_class'], 'Oro\Bundle\UserBundle\Entity\User');
                $this->assertEquals($param['ownership_disabled'], true);
                $this->assertEquals($param['dynamic_fields_disabled'], true);

                return true;
            }));
        $this->type->configureOptions($resolver);
    }

    public function testBuildFormImapAccount()
    {
        $this->registry->expects($this->once())
            ->method('isOauthImapEnabled')
            ->willReturn(true);

        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['add', 'addEventSubscriber'])
            ->getMock();
        $builder->expects($this->once())->method('addEventSubscriber')->with($this->subscriber);
        $builder->expects($this->once())->method('add')->with(
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
        $this->registry->expects($this->once())
            ->method('isOauthImapEnabled')
            ->willReturn(false);

        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['add', 'addEventSubscriber', 'addEventListener'])
            ->getMock();
        $builder->expects($this->once())->method('add')->with(
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
