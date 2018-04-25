<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

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

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->at(0))
            ->method('setDefaults')
            ->with([
                'allow_extra_fields' => true
            ]);

        $this->emailFolderTreeType->configureOptions($resolver);
    }
}
