<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateTranslationTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $configManager = $this->createMock(ConfigManager::class);

        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        $htmlTagHelper = new HtmlTagHelper($htmlTagProvider);
        $htmlTagHelper->setTranslator($this->translator);

        $context = $this->createMock(ContextInterface::class);

        return [
            new PreloadedExtension(
                [
                    EmailTemplateTranslationType::class => new EmailTemplateTranslationType(
                        $this->translator,
                        $this->localizationManager
                    ),
                    OroRichTextType::class =>
                        new OroRichTextType($configManager, $htmlTagProvider, $context, $htmlTagHelper),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testBuildFormWithLocalization(): void
    {
        $form = $this->factory->create(EmailTemplateTranslationType::class, null, [
            'localization' => $this->getEntity(Localization::class, ['id' => 42]),
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => ['any-key' => 'any-val'],
        ]);

        $this->assertTrue($form->has('subject'));
        $this->assertTrue($form->has('content'));

        $wysiwygOptions = $form->get('content')->getConfig()->getOption('wysiwyg_options');
        $this->assertArrayHasKey('any-key', $wysiwygOptions);
        $this->assertSame('any-val', $wysiwygOptions['any-key']);

        $attr = $form->get('content')->getConfig()->getOption('attr');
        $this->assertArrayHasKey('data-wysiwyg-enabled', $attr);
        $this->assertTrue($attr['data-wysiwyg-enabled']);

        $this->assertTrue($form->has('subjectFallback'));
        $this->assertTrue($form->has('contentFallback'));
    }

    public function testBuildFormWithoutLocalization(): void
    {
        $form = $this->factory->create(EmailTemplateTranslationType::class, null, [
            'localization' => null,
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => ['any-key' => 'any-val'],
        ]);

        $this->assertTrue($form->has('subject'));
        $this->assertTrue($form->has('content'));

        $wysiwygOptions = $form->get('content')->getConfig()->getOption('wysiwyg_options');
        $this->assertArrayHasKey('any-key', $wysiwygOptions);
        $this->assertSame('any-val', $wysiwygOptions['any-key']);

        $attr = $form->get('content')->getConfig()->getOption('attr');
        $this->assertArrayHasKey('data-wysiwyg-enabled', $attr);
        $this->assertTrue($attr['data-wysiwyg-enabled']);

        $this->assertFalse($form->has('subjectFallback'));
        $this->assertFalse($form->has('contentFallback'));
    }

    public function testSubmitValid(): void
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);

        $form = $this->factory->create(EmailTemplateTranslationType::class, null, [
            'localization' => $localization,
        ]);

        $data = (new EmailTemplateTranslation())
            ->setLocalization($localization)
            ->setSubject('Old subject')
            ->setSubjectFallback(false)
            ->setContent('Old content')
            ->setContentFallback(false);

        $form->setData($data);

        $submittedData = [
            'subject' => 'Test subject',
            'subjectFallback' => '1',
            'content' => 'Test content',
            'contentFallback' => '1',
        ];

        $form->submit($submittedData);

        $this->assertEquals(
            (new EmailTemplateTranslation())
                ->setLocalization($localization)
                ->setSubject('Test subject')
                ->setSubjectFallback(true)
                ->setContent('Test content')
                ->setContentFallback(true),
            $form->getData()
        );
    }

    public function testSubmitEmptySubjectDefaultLocalization(): void
    {
        $form = $this->factory->create(EmailTemplateTranslationType::class);

        $data = (new EmailTemplateTranslation())
            ->setSubject('Old subject')
            ->setSubjectFallback(false)
            ->setContent('Old content')
            ->setContentFallback(false);

        $form->setData($data);

        $submittedData = [
            'subject' => '',
            'content' => 'Test content'
        ];

        $form->submit($submittedData);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('subject')->isValid());
    }

    public function testSubmitEmptySubjectNonDefaultLocalization(): void
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $form = $this->factory->create(EmailTemplateTranslationType::class, null, [
            'localization' => $localization,
        ]);

        $data = (new EmailTemplateTranslation())
            ->setLocalization($localization)
            ->setSubject('Old subject')
            ->setSubjectFallback(false)
            ->setContent('Old content')
            ->setContentFallback(false);

        $form->setData($data);

        $submittedData = [
            'subject' => '',
            'subjectFallback' => '0',
            'content' => 'Test content',
            'contentFallback' => '1',
        ];

        $form->submit($submittedData);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('subject')->isValid());
    }

    public function testSubmitEmptySubjectNonDefaultLocalizationFallbackEnabled(): void
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $form = $this->factory->create(EmailTemplateTranslationType::class, null, [
            'localization' => $localization,
        ]);

        $data = (new EmailTemplateTranslation())
            ->setLocalization($localization)
            ->setSubject('Old subject')
            ->setSubjectFallback(false)
            ->setContent('Old content')
            ->setContentFallback(false);

        $form->setData($data);

        $submittedData = [
            'subject' => '',
            'subjectFallback' => '1',
            'content' => 'Test content',
            'contentFallback' => '1',
        ];

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->get('subject')->isValid());

        $this->assertEquals(
            (new EmailTemplateTranslation())
                ->setLocalization($localization)
                ->setSubject('')
                ->setSubjectFallback(true)
                ->setContent('Test content')
                ->setContentFallback(true),
            $form->getData()
        );
    }
}
