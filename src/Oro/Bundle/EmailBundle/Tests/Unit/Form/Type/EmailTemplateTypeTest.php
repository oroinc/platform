<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateEntityChoiceType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationCollectionType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateTypeTest extends FormIntegrationTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var EmailTemplateType */
    private $type;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->type = new EmailTemplateType($this->configManager, $this->localizationManager);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $entityProvider = $this->createMock(EntityProvider::class);
        $entityProvider->expects($this->any())
            ->method('getEntities')
            ->willReturn([
                ['name' => \stdClass::class, 'label' => \stdClass::class . '_label'],
                ['name' => \stdClass::class . '_new', 'label' => \stdClass::class . '_new_label'],
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new EmailTemplateEntityChoiceType($entityProvider),
                    new Select2ChoiceType(),
                    new EmailTemplateTranslationCollectionType(),
                    new EmailTemplateTranslationType($translator, $this->localizationManager),
                    new OroRichTextType(
                        $this->createMock(ConfigManager::class),
                        $htmlTagProvider,
                        $this->createMock(ContextInterface::class),
                        new HtmlTagHelper($htmlTagProvider)
                    )
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        EmailTemplate $defaultData,
        array $localizations,
        array $submittedData,
        EmailTemplate $expectedData,
        bool $htmlPurifier
    ) {
        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn($localizations);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_form.wysiwyg_enabled', false, false, null, null],
                ['oro_email.sanitize_html', false, false, null, $htmlPurifier]
            ]);

        $form = $this->factory->create(EmailTemplateType::class, $defaultData);


        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);

        $wysiwygOptions = $form->get('translations')->getConfig()->getOption('wysiwyg_options');

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
        $this->assertTrue((count($wysiwygOptions) === 1) === $htmlPurifier);
        $this->assertFalse($wysiwygOptions['convert_urls']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider(): array
    {
        $localizationA = $this->getLocalization(1);
        $localizationB = $this->getLocalization(42);

        $localizations = [
            $localizationA->getId() => $localizationA,
            $localizationB->getId() => $localizationB,
        ];

        $newEmailTemplate = (new EmailTemplate())
            ->setName('some name')
            ->setEntityName(\stdClass::class)
            ->setType('txt')
            ->setSubject('Default subject')
            ->setContent('Default content')
            ->setTranslations(new ArrayCollection([
                (new EmailTemplateTranslation())
                    ->setLocalization($localizationA)
                    ->setSubject('Subject for "A"')
                    ->setSubjectFallback(false)
                    ->setContentFallback(true),
                (new EmailTemplateTranslation())
                    ->setLocalization($localizationB)
                    ->setSubjectFallback(true)
                    ->setContent('Content for "B"')
                    ->setContentFallback(false),
            ]));

        $editedEmailTemplate = (new EmailTemplate())
            ->setName('new some name')
            ->setEntityName(\stdClass::class . '_new')
            ->setType('html')
            ->setSubject('New default subject')
            ->setContent('New default content')
            ->setTranslations(new ArrayCollection([
                (new EmailTemplateTranslation())
                    ->setLocalization($localizationA)
                    ->setSubjectFallback(true)
                    ->setContent('New content for "A"')
                    ->setContentFallback(false),
                (new EmailTemplateTranslation())
                    ->setLocalization($localizationB)
                    ->setSubject('New subject for "B"')
                    ->setSubjectFallback(false)
                    ->setContentFallback(true),
            ]));

        return [
            'new template' => [
                'defaultData' => new EmailTemplate(),
                'localizations' => $localizations,
                'submittedData' => [
                    'name' => 'some name',
                    'type' => 'txt',
                    'entityName' => \stdClass::class,
                    'translations' => [
                        'default' => [
                            'subject' => 'Default subject',
                            'subjectFallback' => '1',
                            'content' => 'Default content',
                            'contentFallback' => '1',
                        ],
                        1 => [
                            'subject' => 'Subject for "A"',
                            'contentFallback' => '1',
                        ],
                        42 => [
                            'subjectFallback' => '1',
                            'content' => 'Content for "B"',
                        ],
                    ],
                    'parentTemplate' => '',
                ],
                'expectedData' => $newEmailTemplate,
                'htmlPurifier' => false
            ],
            'edit promotion' => [
                'defaultData' => $newEmailTemplate,
                'localizations' => $localizations,
                'submittedData' => [
                    'name' => 'new some name',
                    'type' => 'html',
                    'entityName' => \stdClass::class . '_new',
                    'translations' => [
                        'default' => [
                            'subject' => 'New default subject',
                            'subjectFallback' => '1',
                            'content' => 'New default content',
                            'contentFallback' => '1',
                        ],
                        1 => [
                            'subjectFallback' => '1',
                            'content' => 'New content for "A"',
                        ],
                        42 => [
                            'subject' => 'New subject for "B"',
                            'contentFallback' => '1',
                        ],
                    ],
                ],
                'expectedData' => $editedEmailTemplate,
                'htmlPurifier' => true
            ],
        ];
    }
}
