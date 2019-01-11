<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\EmailBundle\Tests\Unit\Form\Type\Stub\EmailTemplateTranslationTypeStub;
use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Form\Type\GedmoTranslationsType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class EmailTemplateTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localeSettings;

    /**
     * @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localizationManager;

    /**
     * @var EmailTemplateType
     */
    private $type;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->type = new EmailTemplateType(
            $this->configManager,
            $this->localeSettings
        );
        $this->type->setLocalizationManager($this->localizationManager);

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
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    EmailTemplateType::class => $this->type,
                    new EntityChoiceType($entityProvider),
                    new Select2ChoiceType(),
                    EmailTemplateTranslationType::class => new EmailTemplateTranslationTypeStub(
                        $configManager,
                        GedmoTranslationsType::class
                    ),
                ],
                [
                    FormType::class => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    protected function tearDown()
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
     * @param EmailTemplate $defaultData
     * @param array $submittedData
     * @param EmailTemplate $expectedData
     */
    public function testSubmit(EmailTemplate $defaultData, array $submittedData, EmailTemplate $expectedData)
    {
        $this->assertLanguages([777 => 'en'], 'en');
        $form = $this->factory->create(
            EmailTemplateType::class,
            $defaultData
        );

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('some name');
        $emailTemplate->setEntityName(\stdClass::class);
        $emailTemplate->setType('txt');
        $translations = new ArrayCollection([(new EmailTemplateTranslation())->setLocale('en')]);
        $emailTemplate->setTranslations($translations);

        $editedTemplate = clone $emailTemplate;
        $editedTemplate->setName('new some name');
        $editedTemplate->setType('html');
        $editedTemplate->setEntityName(\stdClass::class . '_new');
        $editedTranslations = clone $translations;
        $editedTranslations->first()->setObject($editedTemplate);
        $editedTemplate->setTranslations($editedTranslations);

        return [
            'new template' => [
                'defaultData' => new EmailTemplate(),
                'submittedData' => [
                    'name' => 'some name',
                    'type' => 'txt',
                    'entityName' => \stdClass::class,
                    'translations' => $translations,
                    'parentTemplate' => '',
                ],
                'expectedData' => $emailTemplate,
            ],
            'edit promotion' => [
                'defaultData' => $emailTemplate,
                'submittedData' => [
                    'name' => 'new some name',
                    'type' => 'html',
                    'entityName' => \stdClass::class . '_new',
                    'translations' => $editedTranslations,
                ],
                'expectedData' => $editedTemplate,
            ],
        ];
    }

    /**
     * @param string[] $localizations [id => languageCode, ...]
     * @param string $language
     */
    private function assertLanguages(array $localizations, string $language)
    {
        $localizationIds = array_keys($localizations);
        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn($localizationIds);

        array_walk($localizations, function (&$value, $key) {
            $value = $this->getEntity(
                Localization::class,
                [
                    'id' => $key,
                    'language' => (new Language())->setCode($value)
                ]
            );
        });
        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->with($localizationIds)
            ->willReturn($localizations);
        $this->localeSettings->expects($this->any())
            ->method('getLanguage')
            ->willReturn($language);
    }
}
