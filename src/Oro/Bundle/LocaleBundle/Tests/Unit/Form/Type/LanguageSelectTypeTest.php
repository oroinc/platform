<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LanguageSelectTypeTest extends FormIntegrationTestCase
{
    private LocalizationChoicesProvider&MockObject $provider;
    private LanguageSelectType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = $this->createMock(LocalizationChoicesProvider::class);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [42, $this->getLanguage(42, 'en')]
            ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($repository);

        $this->formType = new LanguageSelectType($this->provider, $doctrine);

        parent::setUp();
    }

    private function getLanguage(int $id, string $code): Language
    {
        $language = new Language();
        ReflectionUtil::setId($language, $id);
        $language->setCode($code);

        return $language;
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testBuildForm()
    {
        $data =  ['English' => '1', 'Spain' => '2'];

        $this->provider->expects($this->once())
            ->method('getLanguageChoices')
            ->with(true)
            ->willReturn($data);

        $form = $this->factory->create(LanguageSelectType::class);

        $choices = $form->createView()->vars['choices'];

        $this->assertEquals(
            [
                new ChoiceView('1', '1', 'English'),
                new ChoiceView('2', '2', 'Spain')
            ],
            $choices
        );
    }

    public function testSubmit()
    {
        $submittedData = '42';
        $expectedData = $this->getLanguage(42, 'en');

        $this->provider->expects($this->once())
            ->method('getLanguageChoices')
            ->with(true)
            ->willReturn(['English' => 42, 'Spain' => 2]);

        $form = $this->factory->create(LanguageSelectType::class);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'language entity' => [
                'submittedData' => '42',
                'expectedData' => $this->getLanguage(42, 'en')
            ]
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $choiceType = $this->createMock(OroChoiceType::class);
        $choiceType->expects($this->any())
            ->method('getParent')
            ->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $choiceType
                ],
                []
            )
        ];
    }
}
