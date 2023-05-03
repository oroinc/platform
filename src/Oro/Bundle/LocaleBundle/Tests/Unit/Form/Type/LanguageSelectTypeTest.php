<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LanguageSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LocalizationChoicesProvider */
    private $provider;

    /** @var LanguageSelectType */
    private $formType;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(LocalizationChoicesProvider::class);

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [42, $this->getEntity(Language::class, ['id' => 42, 'code' => 'en'])]
            ]);

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->any())
            ->method('getClassMetadata')
            ->with(Language::class)
            ->willReturn($metadata);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($manager);

        $this->formType = new LanguageSelectType($this->provider, $registry);
        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(LanguageSelectType::NAME, $this->formType->getName());
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

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(string $submittedData, object $expectedData)
    {
        $data =  ['English' => 42, 'Spain' => 2];

        $this->provider->expects($this->once())
            ->method('getLanguageChoices')
            ->with(true)
            ->willReturn($data);

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
                'expectedData' => $this->getEntity(Language::class, ['id' => 42, 'code' => 'en']),
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
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
