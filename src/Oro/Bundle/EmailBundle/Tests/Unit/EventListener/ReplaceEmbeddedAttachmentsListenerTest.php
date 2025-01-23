<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;
use Oro\Bundle\EmailBundle\EventListener\ReplaceEmbeddedAttachmentsListener;

class ReplaceEmbeddedAttachmentsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReplaceEmbeddedAttachmentsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ReplaceEmbeddedAttachmentsListener();
    }

    /**
     * @dataProvider replaceDataProvider
     */
    public function testReplace($bodyTemplate, array $attachments)
    {
        $email     = new Email();
        $emailBody = new EmailBody();

        $replacements = [];
        $contentIds   = [];
        foreach ($attachments as $attachmentData) {
            $attachment             = new EmailAttachment();
            $emailAttachmentContent = new EmailAttachmentContent();
            $emailAttachmentContent
                ->setContent($attachmentData['content'])
                ->setContentTransferEncoding($attachmentData['transfer_encoding']);
            $attachment
                ->setEmbeddedContentId($attachmentData['content_id'])
                ->setContentType($attachmentData['type'])
                ->setContent($emailAttachmentContent);
            $emailBody->addAttachment($attachment);

            $cid          = 'cid:' . $attachmentData['content_id'];
            $contentIds[] = $cid;
            if ($attachmentData['replace']) {
                $replacements[] = sprintf('data:%s;base64,%s', $attachmentData['type'], $attachmentData['content']);
            } else {
                $replacements[] = $cid;
            }
        }

        $emailBody->setBodyContent(vsprintf($bodyTemplate, $contentIds));
        $email->setEmailBody($emailBody);

        $event = new EmailBodyLoaded($email);
        $this->listener->replace($event);

        $this->assertEquals($email, $event->getEmail());
        $this->assertEquals(
            vsprintf($bodyTemplate, $replacements),
            $event->getEmail()->getEmailBody()->getBodyContent()
        );
    }

    public function replaceDataProvider(): array
    {
        return [
            'one embedded attachment'                 => [
                'body content with embedded content_id #%s#',
                [
                    [
                        'type'              => 'image/jpeg',
                        'content_id'        => 'test_content_id_1',
                        'content'           => 'content to be embedded',
                        'transfer_encoding' => 'base64',
                        'replace'           => true
                    ]
                ]
            ],
            'different types of attachment'           => [
                'body content with embedded attachments content_id1 #%s# content_id2 #%s#',
                [
                    [
                        'type'              => 'image/jpeg',
                        'content_id'        => 'test_content_id_1',
                        'content'           => 'jpeg image source',
                        'transfer_encoding' => 'base64',
                        'replace'           => true
                    ],
                    [
                        'type'              => 'image/png',
                        'content_id'        => 'test_content_id_2',
                        'content'           => 'png image source',
                        'transfer_encoding' => 'base64',
                        'replace'           => true
                    ]
                ]
            ],
            'not supported content transfer encoding' => [
                'embedded content will not be replaced content_id #%s#[invalid encoding]',
                [
                    [
                        'type'              => 'image/jpeg',
                        'content_id'        => 'test_content_id_1',
                        'content'           => 'content',
                        'transfer_encoding' => 'not base64',
                        'replace'           => false
                    ]
                ]
            ],
            'not supported attachment content type'   => [
                'embedded content will not be replaced content_id #%s#[invalid content-type]',
                [
                    [
                        'type'              => 'other/type',
                        'content_id'        => 'test_content_id_1',
                        'content'           => 'content',
                        'transfer_encoding' => 'base64',
                        'replace'           => false
                    ]
                ]
            ]
        ];
    }

    public function testNotSupportedReplace()
    {
        $email     = new Email();
        $emailBody = new EmailBody();

        $replacements = $embeddedContentIds = [];
        $embeddedContentId = 'test_content_id_1';

        $attachment = new EmailAttachment();
        $attachment
            ->setEmbeddedContentId($embeddedContentId)
            ->setContentType('image/jpeg');

        $emailBody->addAttachment($attachment);

        $cid = 'cid:' . $embeddedContentId;
        $embeddedContentIds[] = $cid;
        $replacements[] = $cid;

        $emailBody->setBodyContent(
            vsprintf(
                'body attachment without content for embedded_content_id #%s#',
                $embeddedContentIds
            )
        );
        $email->setEmailBody($emailBody);

        $event = new EmailBodyLoaded($email);
        $this->listener->replace($event);

        $this->assertEquals($email, $event->getEmail());
        $this->assertEquals(
            vsprintf('body attachment without content for embedded_content_id #%s#', $replacements),
            $event->getEmail()->getEmailBody()->getBodyContent()
        );
    }
}
