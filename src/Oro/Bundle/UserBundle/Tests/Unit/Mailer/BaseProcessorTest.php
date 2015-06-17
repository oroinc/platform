<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Entity\User;

class BaseProcessorTest extends AbstractProcessorTest
{
    /**
     * @dataProvider sendEmailResultProvider
     *
     * @param User $user
     * @param string $emailType
     * @param string $expectedEmailType
     */
    public function testSendEmail(User $user, $emailType, $expectedEmailType)
    {
        $templateName = 'email_template_name';
        $templateParams = ['entity' => $user];
        $expectedMessage = $this->buildMessage($user->getEmail(), 'email subject', 'email body', $expectedEmailType);

        $this->assertSendCalled($templateName, $templateParams, $expectedMessage, $emailType);

        $this->mailProcessor->getEmailTemplateAndSendEmail($user, $templateName, $templateParams);
    }

    /**
     * @return array
     */
    public function sendEmailResultProvider()
    {
        $user = new User();
        $user->setEmail('email_to@example.com');

        return [
            [
                'user' => $user,
                'emailType' => 'txt',
                'expectedEmailType' => 'text/plain'
            ],
            [
                'user' => $user,
                'emailType' => 'html',
                'expectedEmailType' => 'text/html'
            ]
        ];
    }
}
