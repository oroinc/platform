<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SegmentBundle\Form\Type\SegmentType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class SegmentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SegmentType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new SegmentType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'name',
                'text',
                ['required' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'entity',
                'oro_segment_entity_choice',
                ['required' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'type',
                EntityType::class,
                [
                    'class'       => 'OroSegmentBundle:SegmentType',
                    'choice_label' => 'label',
                    'required'    => true,
                    'empty_value' => 'oro.segment.form.choose_segment_type',
                    'tooltip'     => 'oro.segment.type.tooltip_text'
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'recordsLimit',
                'integer',
                ['required' => false]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'description',
                'textarea',
                ['required' => false]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'definition',
                'hidden',
                ['required' => false]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'column_column_field_choice_options' => [
                        'exclude_fields' => ['relation_type'],
                    ],
                    'column_column_choice_type'   => 'hidden',
                    'filter_column_choice_type'   => 'oro_entity_field_select',
                    'data_class'                  => 'Oro\Bundle\SegmentBundle\Entity\Segment',
                    'intention'                   => 'segment',
                    'query_type'                  => 'segment',
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_segment', $this->type->getName());
    }
}
