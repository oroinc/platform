<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\EmailBundle\Form\Type\ContextsSelectType;

class ContextsSelectTypeTest extends TypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'genemu_jqueryselect2_hidden' => new Select2Type('hidden')
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addModelTransformer');
        $type = new ContextsSelectType($this->em);
        $type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'tooltip' => false,
                    'configs' => [
                        'placeholder'        => 'oro.email.contexts.placeholder',
                        'allowClear'         => true,
                        'multiple'           => true,
                        'route_name'         => 'oro_api_get_search_autocomplete',
                        'separator'          => ';',
                        'containerCssClass'  => 'taggable-email',
                        'minimumInputLength' => 1,
                    ]
                ]
            );

        $type = new ContextsSelectType($this->em);
        $type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $type = new ContextsSelectType($this->em);
        $this->assertEquals('genemu_jqueryselect2_hidden', $type->getParent());

    }

    public function testGetName()
    {
        $type = new ContextsSelectType($this->em);
        $this->assertEquals('oro_email_contexts_select', $type->getName());
    }
}
