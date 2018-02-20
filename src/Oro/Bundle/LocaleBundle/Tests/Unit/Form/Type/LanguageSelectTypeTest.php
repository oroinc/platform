<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\PreloadedExtension;

class LanguageSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LocalizationChoicesProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var LanguageSelectType */
    protected $formType;

    public function setUp()
    {
        parent::setUp();

        $this->provider = $this->createMock(LocalizationChoicesProvider::class);

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->any())->method('getSingleIdentifierFieldName')->willReturn('id');

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())->method('find')->willReturnMap(
            [
                [42, $this->getEntity(Language::class, ['id' => 42, 'code' => 'en'])]
            ]
        );

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->any())->method('getClassMetadata')->with(Language::class)->willReturn($metadata);
        $manager->expects($this->any())->method('getRepository')->with(Language::class)->willReturn($repository);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Language::class)
            ->willReturn($manager);

        $this->formType = new LanguageSelectType($this->provider, $this->registry);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(LanguageSelectType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $data =  ['1' => 'English', '2' => 'Spain'];

        $this->provider->expects($this->once())->method('getLanguageChoices')->with(true)->willReturn($data);

        $form = $this->factory->create($this->formType);

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
     *
     * @param string $submittedData
     * @param object $expectedData
     */
    public function testSubmit($submittedData, $expectedData)
    {
        $data =  ['42' => 'English', '2' => 'Spain'];

        $this->provider->expects($this->once())->method('getLanguageChoices')->with(true)->willReturn($data);

        $form = $this->factory->create($this->formType);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'language entity' => [
                'submittedData' => '42',
                'expectedData' => $this->getEntity(Language::class, ['id' => 42, 'code' => 'en']),
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $choiceType = $this->getMockBuilder(OroChoiceType::class)
            ->setMethods(['configureOptions', 'getParent'])
            ->disableOriginalConstructor()
            ->getMock();
        $choiceType->expects($this->any())->method('getParent')->willReturn('choice');

        return [
            new PreloadedExtension([OroChoiceType::NAME => $choiceType], [])
        ];
    }
}
