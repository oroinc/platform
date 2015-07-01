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
            ->with('subFolders', 'collection', ['type' => 'oro_email_email_folder',])
            ->willReturn($builder);

        $this->emailFolderType->buildForm($builder, []);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_email_folder', $this->emailFolderType->getName());
    }
}
