<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;

class EmailBodyBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailBodyBuilder
     */
    protected $emailBodyBuilder;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailBodyBuilder = new EmailBodyBuilder($this->configManager);
    }

    public function testCreateEmailBody()
    {
        $this->emailBodyBuilder->setEmailBody('test', true);
        $body = $this->emailBodyBuilder->getEmailBody();
        $this->assertEquals(true, $body->getBodyIsText());
        $this->assertEquals('test', $body->getBodyContent());
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetEmptyEmailBody()
    {
        $this->emailBodyBuilder->getEmailBody();
    }

    /**
     * @expectedException \LogicException
     */
    public function testAddEmailAttachmentWithoutBody()
    {
        $this->emailBodyBuilder->addEmailAttachment(
            'test',
            'content',
            'ct',
            '',
            1,
            1
        );
    }

    /**
     * @param bool $expected
     * @param [] $data
     * @param bool $configSyncEnabled
     * @param int $configSyncMaxSize
     *
     * @dataProvider addAttachmentProvider
     */
    public function testAddAttachment($expected, $data, $configSyncEnabled, $configSyncMaxSize)
    {
        $this->emailBodyBuilder->setEmailBody('test', true);

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with(EmailBodyBuilder::ORO_EMAIL_ATTACHMENT_SYNC_ENABLE)
            ->willReturn($configSyncEnabled);
        if ($configSyncEnabled) {
            $this->configManager->expects($this->at(1))
                ->method('get')
                ->with(EmailBodyBuilder::ORO_EMAIL_ATTACHMENT_SYNC_MAX_SIZE)
                ->willReturn($configSyncMaxSize);
        }

        $this->emailBodyBuilder->addEmailAttachment(
            'test',
            'content',
            'ct',
            $data['contentTransferEncoding'],
            $data['embeddedContentId'],
            $data['contentSize']
        );

        $body = $this->emailBodyBuilder->getEmailBody();
        $this->assertEquals($expected, $body->getHasAttachments());
    }

    public function addAttachmentProvider()
    {
        return [
            'not set' => [
                'expected' => false,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 100000,
                ],
                'configSyncEnabled' => null,
                'configSyncMaxSize' => null,
            ],
            'unlimit' => [
                'expected' => true,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 100000,
                ],
                'configSyncEnabled' => true,
                'configSyncMaxSize' => 0,
            ],
            'disabled' => [
                'expected' => false,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 100000,
                ],
                'configSyncEnabled' => false,
                'configSyncMaxSize' => 0,
            ],
            'less than allow' => [
                'expected' => true,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 100,
                ],
                'configSyncEnabled' => true,
                'configSyncMaxSize' => 0.1,
            ],
            'more than allow' => [
                'expected' => false,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 1000 * 1000 * 5,
                ],
                'configSyncEnabled' => true,
                'configSyncMaxSize' => 0.1,
            ],
            'allowed size' => [
                'expected' => true,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 1000 * 1000 * 0.49 * 4 / 3,
                ],
                'configSyncEnabled' => true,
                'configSyncMaxSize' => 0.5,
            ],
            'calc by content' => [
                'expected' => true,
                'data' => [
                    'contentTransferEncoding' => 'base64',
                    'embeddedContentId' => 123,
                    'contentSize' => 0,
                ],
                'configSyncEnabled' => true,
                'configSyncMaxSize' => 0.5,
            ],
        ];
    }
}
