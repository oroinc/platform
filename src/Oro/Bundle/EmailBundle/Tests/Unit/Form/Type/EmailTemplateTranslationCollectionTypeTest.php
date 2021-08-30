<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationCollectionType;
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

class EmailTemplateTranslationCollectionTypeTest extends FormIntegrationTestCase
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

    public function testBuildForm(): void
    {
        $form = $this->factory->create(EmailTemplateTranslationCollectionType::class, null, [
            'localizations' => [
                $this->getEntity(Localization::class, ['id' => 42]),
                $this->getEntity(Localization::class, ['id' => 54]),
                $this->getEntity(Localization::class, ['id' => 88]),
            ],
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => ['any-key' => 'any-val'],
        ]);

        $this->assertTrue($form->has('default'));
        $this->assertTrue($form->has(42));
        $this->assertTrue($form->has(54));
        $this->assertTrue($form->has(88));
    }

    public function testSubmit(): void
    {
        /** @var Localization $localizationExist */
        $localizationExist = $this->getEntity(Localization::class, ['id' => 42]);

        /** @var Localization $localizationNew */
        $localizationNew = $this->getEntity(Localization::class, ['id' => 54]);

        $form = $this->factory->create(EmailTemplateTranslationCollectionType::class, null, [
            'localizations' => [
                $localizationExist,
                $localizationNew,
            ],
        ]);

        $data = new ArrayCollection([
            'default' => (new EmailTemplateTranslation())
                ->setSubject('Default subject')
                ->setSubjectFallback(false)
                ->setContent('Default content')
                ->setContentFallback(false),

            42 => (new EmailTemplateTranslation())
                ->setLocalization($localizationExist)
                ->setSubject('Old subject')
                ->setSubjectFallback(false)
                ->setContent('Old content')
                ->setContentFallback(false),
        ]);
        $form->setData($data);

        $submittedData = [
            'default' => [
                'subject' => 'New default subject',
                'subjectFallback' => '1',
                'content' => 'New default content',
                'contentFallback' => '1',
            ],
            42 => [
                'subject' => 'Test subject 42',
                'contentFallback' => '1',
            ],
            54 => [
                'subjectFallback' => '1',
                'content' => 'Test content 54',
            ],
        ];

        $form->submit($submittedData);

        $this->assertEquals(new ArrayCollection([
            'default' => (new EmailTemplateTranslation())
                ->setSubject('New default subject')
                ->setSubjectFallback(false)
                ->setContent('New default content')
                ->setContentFallback(false),

            42 => (new EmailTemplateTranslation())
                ->setLocalization($localizationExist)
                ->setSubject('Test subject 42')
                ->setSubjectFallback(false)
                ->setContent(null)
                ->setContentFallback(true),

            54 => (new EmailTemplateTranslation())
                ->setLocalization($localizationNew)
                ->setSubject(null)
                ->setSubjectFallback(true)
                ->setContent('Test content 54')
                ->setContentFallback(false),
        ]), $form->getData());
    }
}
