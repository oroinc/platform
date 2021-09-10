<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailFolderTreeTypeTest extends FormIntegrationTestCase
{
    /** @var EmailFolderTreeType */
    private $emailFolderTreeType;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->emailFolderTreeType = new EmailFolderTreeType();

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['allow_extra_fields' => true]);

        $this->emailFolderTreeType->configureOptions($resolver);
    }
}
