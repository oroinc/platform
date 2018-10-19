<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SearchEntityFilterTypeTest extends FormIntegrationTestCase
{
    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizationHelper;

    /** @var SearchEntityFilterType */
    protected $type;

    protected function setUp()
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->type = new SearchEntityFilterType($this->entityNameResolver, $this->localizationHelper);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined(['class', 'choices']);

        $this->type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(
            [
                'class' => 'stdClass',
                'choices' => ['choice1', 'choice2'],
            ]
        );

        $this->assertEquals(
            [
                'class' => 'stdClass',
                'choices' => ['choice1', 'choice2'],
                'field_options' => [
                    'multiple' => true,
                    'class' => 'stdClass',
                    'choices' => ['choice1', 'choice2'],
                    'choice_label' => [$this->type, 'getLocalizedChoiceLabel'],
                ],
            ],
            $resolvedOptions
        );
    }

    public function testConfigureOptionsWithoutClassAndChoices()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        $this->assertEquals(
            [
                'field_options' => [
                    'multiple' => true,
                    'class' => null,
                    'choices' => null,
                    'choice_label' => [$this->type, 'getLocalizedChoiceLabel'],
                ],
                'choices' => null,
            ],
            $resolvedOptions
        );
    }

    public function testChoiceLabelOption()
    {
        $localization = new Localization();
        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $entity = (object)['id' => 2, 'label' => 'label2'];
        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($entity, null, $localization)
            ->willReturn('resolved-label');

        $form = $this->factory->create(SearchEntityFilterType::class, [], []);
        $formOptions = $form->getConfig()->getOptions();
        $this->assertEquals(
            'resolved-label',
            $formOptions['field_options']['choice_label']($entity)
        );
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SearchEntityFilterType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityFilterType::class, $this->type->getParent());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $translator = $this->createMock(TranslatorInterface::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new EntityFilterType($translator),
                    new ChoiceFilterType($translator),
                    new FilterType($translator),
                    EntityType::class => new EntityTypeStub([])
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
