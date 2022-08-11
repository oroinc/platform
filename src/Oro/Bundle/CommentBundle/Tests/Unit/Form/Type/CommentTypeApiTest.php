<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber;
use Oro\Bundle\CommentBundle\Form\Type\CommentTypeApi;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlank;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentTypeApiTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'message',
                    OroResizeableRichTextType::class,
                    [
                        'required' => true,
                        'label'    => 'oro.comment.message.label',
                        'attr'     => [
                            'class'       => 'comment-text-field',
                            'placeholder' => 'oro.comment.message.placeholder'
                        ],
                        'constraints' => [ new HtmlNotBlank() ]
                    ]
                ],
                [
                    'attachment',
                    ImageType::class,
                    ['label' => 'oro.comment.attachment.label', 'required' => false]
                ]
            )
            ->willReturnSelf();
        $builder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->isInstanceOf(PatchSubscriber::class)],
                [$this->isInstanceOf(CommentSubscriber::class)]
            )
            ->willReturnSelf();

        $formType = new CommentTypeApi($this->configManager);
        $formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'      => Comment::class,
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
