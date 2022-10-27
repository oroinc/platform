<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;

class EmailBodyBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EmailBodyBuilder */
    private $emailBodyBuilder;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->emailBodyBuilder = new EmailBodyBuilder($this->configManager);
    }

    public function testCreateEmailBody()
    {
        $this->emailBodyBuilder->setEmailBody('test', true);
        $body = $this->emailBodyBuilder->getEmailBody();
        $this->assertEquals(true, $body->getBodyIsText());
        $this->assertEquals('test', $body->getBodyContent());
        $this->assertEquals('test', $body->getTextBody());
    }

    public function testGetEmptyEmailBody()
    {
        $this->expectException(\LogicException::class);
        $this->emailBodyBuilder->getEmailBody();
    }

    public function testAddEmailAttachmentWithoutBody()
    {
        $this->expectException(\LogicException::class);
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
     * @dataProvider addAttachmentProvider
     */
    public function testAddAttachment(
        bool $expected,
        array $data,
        ?bool $configSyncEnabled,
        int|float|null $configSyncMaxSize
    ) {
        $this->emailBodyBuilder->setEmailBody('test', true);

        if ($configSyncEnabled) {
            $this->configManager->expects($this->exactly(2))
                ->method('get')
                ->withConsecutive(
                    [EmailBodyBuilder::ORO_EMAIL_ATTACHMENT_SYNC_ENABLE],
                    [EmailBodyBuilder::ORO_EMAIL_ATTACHMENT_SYNC_MAX_SIZE]
                )
                ->willReturn(
                    $configSyncEnabled,
                    $configSyncMaxSize
                );
        } else {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with(EmailBodyBuilder::ORO_EMAIL_ATTACHMENT_SYNC_ENABLE)
                ->willReturn($configSyncEnabled);
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

    public function addAttachmentProvider(): array
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
