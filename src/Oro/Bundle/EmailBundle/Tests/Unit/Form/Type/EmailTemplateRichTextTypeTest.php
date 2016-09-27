<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateRichTextType;

class EmailTemplateRichTextTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailTemplateRichTextType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new EmailTemplateRichTextType('/tmp');
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->once())
            ->method('addModelTransformer');
        $options = [
            'wysiwyg_options' => [
                'valid_elements' => [
                    'a',
                ],
            ],
        ];

        $this->type->buildForm($builder, $options);
    }
}
