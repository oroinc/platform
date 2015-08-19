<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;

class EmailFolderTreeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EmailFolderTreeType
     */
    protected $emailFolderTreeType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->emailFolderTreeType = new EmailFolderTreeType();

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
                'type' => 'oro_email_email_folder',
                'allow_add' => true,
            ]);

        $this->emailFolderTreeType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->emailFolderTreeType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_email_folder_tree', $this->emailFolderTreeType->getName());
    }
}
