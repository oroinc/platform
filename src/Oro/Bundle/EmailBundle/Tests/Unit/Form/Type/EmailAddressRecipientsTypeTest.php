<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressRecipientsType;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailAddressRecipientsTypeTest extends TypeTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_email.minimum_input_length')
            ->willReturn(2);

        parent::setUp();
    }

    public function testFormShouldBeSubmittedAndViewShouldContainsRouteParameters(): void
    {
        $email = new Email();
        $email->setEntityClass('entityClass_param');
        $email->setEntityId('entityId_param');

        $form = $this->factory->createBuilder(FormType::class, $email)
            ->add('to', EmailAddressRecipientsType::class)
            ->getForm();

        $form->submit([]);

        $expectedRouteParameters = [
            'entityClass' => 'entityClass_param',
            'entityId'    => 'entityId_param',
        ];

        $view = $form->createView();
        $configs = $view->children['to']->vars['configs'];

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertArrayHasKey('route_parameters', $configs);
        $this->assertEquals($configs['route_parameters'], $expectedRouteParameters);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'tooltip' => false,
                    'error_bubbling' => false,
                    'empty_data' => [],
                    'configs' => [
                        'allowClear' => true,
                        'multiple' => true,
                        'separator' => EmailRecipientsHelper::EMAIL_IDS_SEPARATOR,
                        'route_name' => 'oro_email_autocomplete_recipient',
                        'type' => 'POST',
                        'minimumInputLength' => 2,
                        'per_page' => 100,
                        'containerCssClass' => 'taggable-email',
                        'tags' => [],
                        'component' => 'email-recipients',
                    ],
                ]
            );

        $form = new EmailAddressRecipientsType($this->configManager);
        $form->configureOptions($resolver);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $emailAddressRecipients = new EmailAddressRecipientsType($this->configManager);

        return [
            new PreloadedExtension(
                [$emailAddressRecipients],
                []
            ),
        ];
    }
}
