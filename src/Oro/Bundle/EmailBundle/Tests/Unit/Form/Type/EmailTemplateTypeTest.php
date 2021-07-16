<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationCollectionType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class EmailTemplateTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

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
     * @return array
     */
    protected function getExtensions()
    {
        /** @var EntityProvider|\PHPUnit\Framework\MockObject\MockObject $entityProvider */
        $entityProvider = $this->createMock(EntityProvider::class);
        $entityProvider->expects($this->any())
            ->method('getEntities')
            ->willReturn([
                ['name' => \stdClass::class, 'label' => \stdClass::class . '_label'],
                ['name' => \stdClass::class . '_new', 'label' => \stdClass::class . '_new_label'],
            ]);
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject $htmlTagProvider */
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        $htmlTagHelper = new HtmlTagHelper($htmlTagProvider);

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);

        return [
            new PreloadedExtension(
                [
                    EmailTemplateType::class => $this->type,
                    new EntityChoiceType($entityProvider),
                    new Select2ChoiceType(),
                    new EmailTemplateTranslationCollectionType(),

                    new EmailTemplateTranslationType(
                        $translator,
                        $this->localizationManager
                    ),
                    OroRichTextType::class =>
                        new OroRichTextType($configManager, $htmlTagProvider, $context, $htmlTagHelper),
                ],
                [
                    FormType::class => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    protected function tearDown(): void
    {
        unset($this->type);
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
        EmailTemplate $expectedData
    ) {
        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn($localizations);

        $form = $this->factory->create(EmailTemplateType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        /** @var Localization $localizationA */
        $localizationA = $this->getEntity(Localization::class, ['id' => 1]);

        /** @var Localization $localizationB */
        $localizationB = $this->getEntity(Localization::class, ['id' => 42]);

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
            ],
        ];
    }
}
