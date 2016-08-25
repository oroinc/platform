<?php

namespace Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\Email;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Class EmailNotificationManagerTest
 *
 * @package Oro\src\Oro\Bundle\EmailBundle\Tests\Unit\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailNotificationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var EmailNotificationManager */
    protected $emailNotificationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getNewEmails','getCountNewEmails'])
            ->getMock();
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);


        $htmlTagProvider = $this->getMockBuilder('Oro\Bundle\FormBundle\Provider\HtmlTagProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->htmlTagHelper = new HtmlTagHelper($htmlTagProvider, '');

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()->getMock();
        $this->router->expects($this->any())->method('generate')->willReturn('oro_email_email_reply');

        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->configManager->expects($this->any())->method('getEntityMetadata')->willReturn($metadata);

        $this->emailNotificationManager = new EmailNotificationManager(
            $this->entityManager,
            $this->htmlTagHelper,
            $this->router,
            $this->configManager
        );
    }

    public function testGetEmails()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();
        $testEmails = $this->getEmails($user);
        $this->repository->expects($this->once())->method('getNewEmails')->willReturn($testEmails);
        $maxEmailsDisplay = 1;
        $emails = $this->emailNotificationManager->getEmails($user, $organization, $maxEmailsDisplay, null);

        $htmlCleanBody = <<<CLEANBODY
Lorem ipsum 
 dolor sit amet, consectetur adipiscing elit. 


  
    Integer
    sagittis
  
  
    ornare
    dolor
CLEANBODY;

        $this->assertEquals(
            [
                [
                    'replyRoute' => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply',
                    'forwardRoute' => 'oro_email_email_reply',
                    'id' => 1,
                    'seen' => 0,
                    'subject' => 'subject',
                    'bodyContent' => 'bodyContent',
                    'fromName' => 'fromName',
                    'linkFromName' => 'oro_email_email_reply',
                ],
                [
                    'replyRoute' => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply',
                    'forwardRoute' => 'oro_email_email_reply',
                    'id' => 2,
                    'seen' => 1,
                    'subject' => 'subject_1',
                    'bodyContent' => 'bodyContent_1',
                    'fromName' => 'fromName_1',
                    'linkFromName' => 'oro_email_email_reply',
                ],
                [
                    'replyRoute' => 'oro_email_email_reply',
                    'replyAllRoute' => 'oro_email_email_reply',
                    'forwardRoute' => 'oro_email_email_reply',
                    'id' => 3,
                    'seen' => 0,
                    'subject' => 'subject_3',
                    'bodyContent' => $htmlCleanBody,
                    'fromName' => 'fromName_1',
                    'linkFromName' => 'oro_email_email_reply',
                ]
            ],
            $emails
        );
    }

    public function testGetCountNewEmails()
    {
        $this->repository->expects($this->once())->method('getCountNewEmails')->willReturn(1);
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();
        $count = $this->emailNotificationManager->getCountNewEmails($user, $organization, null);
        $this->assertEquals(1, $count);
    }

    /**
     * @param EmailOwnerInterface $user
     *
     * @return array
     */
    protected function getEmails($user)
    {
        $firstEmail = $this->prepareEmailUser(
            [
                'getId'          => 1,
                'getSubject'     => 'subject',
                'getFromName'    => 'fromName',
                'getBodyContent' => 'bodyContent',
            ],
            $user,
            false
        );

        $secondEmail = $this->prepareEmailUser(
            [
                'getId'          => 2,
                'getSubject'     => 'subject_1',
                'getBodyContent' => 'bodyContent_1',
                'getFromName'    => 'fromName_1',

            ],
            $user,
            true
        );

        $html1Body = <<<EMAILBODY
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 
Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
   <head>
      <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
      <meta name="viewport" content="width=device-width"/>
      <title></title>
 <body style="padding:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<style type="text/css">
body {font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;}
.phoenix-email-container {width: 512px !important;}
</style>
<script type="text/javascript">
    document.write ('some text');
  </script>
<p>Lorem <b>ipsum</b> </p>
<span> dolor sit amet, consectetur adipiscing elit. </span>
<table>
<table>
  <tr>
    <th>Integer</th>
    <th>sagittis</th>
  </tr>
  <tr>
    <td>ornare</td>
    <td>dolor</td>
  </tr>
</table>
</body>
</html>
EMAILBODY;

        $htmlEmail = $this->prepareEmailUser(
            [
                'getId'          => 3,
                'getSubject'     => 'subject_3',
                'getFromName'    => 'fromName_1',
                'getBodyContent' => $html1Body
            ],
            $user,
            false
        );

        return [$firstEmail, $secondEmail, $htmlEmail];
    }

    /**
     * @param array $values
     * @param EmailOwnerInterface $user
     * @param bool $seen
     *
     * @return EmailUser
     */
    protected function prepareEmailUser($values, $user, $seen)
    {
        $emailBody = new EmailBody();
        $emailBody->setBodyContent($values['getBodyContent']);

        $email = new Email();
        $email->setId($values['getId']);
        $email->setSubject($values['getSubject']);
        $email->setFromName($values['getFromName']);

        $emailAddress = new EmailAddress();
        $emailAddress->setOwner($user);

        $email->setFromEmailAddress($emailAddress);
        $email->setEmailBody($emailBody);

        $emailUser = new EmailUser();
        $emailUser->setEmail($email);
        $emailUser->setSeen($seen);

        return $emailUser;
    }
}
