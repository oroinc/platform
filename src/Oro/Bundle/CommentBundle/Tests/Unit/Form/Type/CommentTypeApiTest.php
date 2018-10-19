<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\Type\CommentTypeApi;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentTypeApiTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuildForm()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject | FormBuilderInterface $builder */
        $builder = $this->createMock('\Symfony\Component\Form\FormBuilder');
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'message',
                OroResizeableRichTextType::class,
                [
                    'required' => true,
                    'label'    => 'oro.comment.message.label',
                    'attr'     => [
                        'class'       => 'comment-text-field',
                        'placeholder' => 'oro.comment.message.placeholder'
                    ],
                    'constraints' => [ new NotBlank() ]
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'attachment',
                ImageType::class,
                ['label' => 'oro.comment.attachment.label', 'required' => false]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber'))
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber'))
            ->will($this->returnSelf());
        $formType = new CommentTypeApi($this->configManager);
        $formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject | OptionsResolver $resolver */
        $resolver = $this->createMock('\Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'      => Comment::ENTITY_NAME,
                'csrf_token_id'   => 'comment',
                'csrf_protection' => false,
            ]);

        $formType = new CommentTypeApi($this->configManager);
        $formType->configureOptions($resolver);
    }

    public function testReturnFormName()
    {
        $formType = new CommentTypeApi($this->configManager);
        $this->assertEquals('oro_comment_api', $formType->getName());
    }
}
