<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;

class EmailTypeTest extends TypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    protected function setUp()
    {
        parent::setUp();
        $this->securityContext  = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
    }

    protected function getExtensions()
    {
        $emailAddressType  = new EmailAddressType($this->securityContext);
        $translatableType = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->disableOriginalConstructor()
            ->getMock();
        $translatableType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(TranslatableEntityType::NAME));

        $select2ChoiceType = new Select2Type(TranslatableEntityType::NAME);
        $emailTemplateList = new EmailTemplateSelectType();

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider = new HtmlTagProvider();
        $richTextType = new OroRichTextType($configManager, $htmlTagProvider);

        return [
            new PreloadedExtension(
                [
                    TranslatableEntityType::NAME  => $translatableType,
                    $select2ChoiceType->getName() => $select2ChoiceType,
                    $emailTemplateList->getName() => $emailTemplateList,
                    $emailAddressType->getName()  => $emailAddressType,
                    $richTextType->getName()      => $richTextType
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider messageDataProvider
     * @param array $formData
     * @param array $to
     * @param array $cc
     * @param array $bcc
     */
    public function testSubmitValidData($formData, $to, $cc, $bcc)
    {
        $body = '';
        if (isset($formData['body'])) {
            $body = $formData['body'];
        }
        $type = new EmailType($this->securityContext);
        $form = $this->factory->create($type);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        /** @var Email $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
        $this->assertEquals('test_grid', $result->getGridName());
        $this->assertEquals($formData['from'], $result->getFrom());
        $this->assertEquals($to, $result->getTo());
        $this->assertEquals($cc, $result->getCc());
        $this->assertEquals($bcc, $result->getBcc());
        $this->assertEquals($formData['subject'], $result->getSubject());
        $this->assertEquals($body, $result->getBody());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                    'intention'          => 'email',
                    'csrf_protection'    => true,
                    'cascade_validation' => true
                ]
            );

        $type = new EmailType($this->securityContext);
        $type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $type = new EmailType($this->securityContext);
        $this->assertEquals('oro_email_email', $type->getName());
    }


    public function messageDataProvider()
    {
        return [
            [
                [
                    'gridName' => 'test_grid',
                    'from' => 'John Smith <john@example.com>',
                    'to' => 'John Smith 1 <john1@example.com>; "John Smith 2" <john2@example.com>; john3@example.com',
                    'subject' => 'Test subject',
                    'type' => 'text',
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'],
                [],
                [],
            ],
            [
                [
                    'gridName' => 'test_grid',
                    'from' => 'John Smith <john@example.com>',
                    'to' => 'John Smith 1 <john1@example.com>; "John Smith 2" <john2@example.com>; john3@example.com',
                    'cc' => 'John Smith 4 <john4@example.com>; "John Smith 5" <john5@example.com>; john6@example.com',
                    'bcc' => 'John Smith 7 <john7@example.com>; "John Smith 8" <john8@example.com>; john9@example.com',
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                    'type' => 'text',
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'],
                ['John Smith 4 <john4@example.com>', '"John Smith 5" <john5@example.com>', 'john6@example.com'],
                ['John Smith 7 <john7@example.com>', '"John Smith 8" <john8@example.com>', 'john9@example.com'],
            ],
        ];
    }
}
