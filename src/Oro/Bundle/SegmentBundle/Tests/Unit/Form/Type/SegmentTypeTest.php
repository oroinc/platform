<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentEntityChoiceType;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SegmentTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SegmentType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new SegmentType();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);

        $builder->expects($this->exactly(6))
            ->method('add')
            ->withConsecutive(
                ['name', TextType::class, ['required' => true]],
                ['entity', SegmentEntityChoiceType::class, ['required' => true]],
                [
                    'type',
                    EntityType::class,
                    [
                        'class'        => 'OroSegmentBundle:SegmentType',
                        'choice_label' => 'label',
                        'required'     => true,
                        'placeholder'  => 'oro.segment.form.choose_segment_type',
                        'tooltip'      => 'oro.segment.type.tooltip_text'
                    ]
                ],
                ['recordsLimit', IntegerType::class, ['required' => false]],
                ['description', TextareaType::class, ['required' => false]],
                ['definition', HiddenType::class, ['required' => false]]
            )
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'column_column_field_choice_options' => [
                        'exclude_fields' => ['relationType'],
                    ],
                    'column_column_choice_type'          => HiddenType::class,
                    'filter_column_choice_type'          => EntityFieldSelectType::class,
                    'data_class'                         => Segment::class,
                    'csrf_token_id'                      => 'segment',
                    'query_type'                         => 'segment',
                ]
            );

        $this->type->configureOptions($resolver);
    }
}
