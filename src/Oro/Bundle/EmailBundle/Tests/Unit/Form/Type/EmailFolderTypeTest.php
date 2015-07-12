<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderType;

class EmailFolderTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EmailFolderType|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailFolderType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->emailFolderType = new EmailFolderType();

        parent::setUp();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->at(0))
            ->method('setDefaults')
            ->with([
                'data_class' => 'Oro\Bundle\EmailBundle\Entity\EmailFolder',
                'nesting_level' => 10,
            ]);

        $this->emailFolderType->setDefaultOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with('syncEnabled', 'checkbox')
            ->willReturn($builder);

        $builder->expects($this->at(1))
            ->method('add')
            ->with('fullName', 'hidden')
            ->willReturn($builder);

        $builder->expects($this->at(2))
            ->method('add')
            ->with('name', 'hidden')
            ->willReturn($builder);

        $builder->expects($this->at(3))
            ->method('add')
            ->with('type', 'hidden')
            ->willReturn($builder);

        $builder->expects($this->at(4))
            ->method('add')
            ->with('subFolders', 'collection', [
                'type' => 'oro_email_email_folder',
                'allow_add' => true,
                'options' => [
                    'nesting_level' => 4,
                ],
            ])
            ->willReturn($builder);

        $this->emailFolderType->buildForm($builder, ['nesting_level' => 5]);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_email_folder', $this->emailFolderType->getName());
    }
}
