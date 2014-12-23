<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\Type\CommentType;

class CommentTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject | FormBuilderInterface $builder */
        $builder = $this->getMock('\Symfony\Component\Form\FormBuilder', [], [], '', false);
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'message',
                'textarea',
                [
                    'required' => true,
                    'label'    => 'oro.note.message.label',
                    'attr'     => [
                        'class' => 'comment-text-field',
                        'placeholder' => 'oro.comment.message.placeholder'
                    ],
                ]
            )
            ->will($this->returnSelf());
        $formType = new CommentType();
        $formType->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject | OptionsResolverInterface $resolver */
        $resolver = $this->getMock('\Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'              => Comment::ENTITY_NAME,
                'intention'               => 'comment',
                'ownership_disabled'      => true,
                'csrf_protection'         => true,
                'cascade_validation'      => true
            ]);

        $formType = new CommentType();
        $formType->setDefaultOptions($resolver);
    }

    public function testReturnFormName()
    {
        $formType = new CommentType();
        $this->assertEquals('oro_comment', $formType->getName());
    }
}
