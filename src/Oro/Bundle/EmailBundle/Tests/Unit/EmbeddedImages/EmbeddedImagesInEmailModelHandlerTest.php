<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmbeddedImages;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImage;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesExtractor;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInEmailModelHandler;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory;

class EmbeddedImagesInEmailModelHandlerTest extends \PHPUnit\Framework\TestCase
{
    private EmbeddedImagesExtractor|\PHPUnit\Framework\MockObject\MockObject $embeddedImagesExtractor;

    private EmailEntityBuilder|\PHPUnit\Framework\MockObject\MockObject $emailEntityBuilder;

    private EmbeddedImagesInEmailModelHandler $handler;

    protected function setUp(): void
    {
        $this->embeddedImagesExtractor = $this->createMock(EmbeddedImagesExtractor::class);
        $this->emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $emailModelsFactory = new Factory();

        $this->handler = new EmbeddedImagesInEmailModelHandler(
            $this->embeddedImagesExtractor,
            $this->emailEntityBuilder,
            $emailModelsFactory
        );
    }

    public function testHandleEmbeddedImagesDoesNothingWhenEmptyBody(): void
    {
        $emailModel = new EmailModel();

        $this->handler->handleEmbeddedImages($emailModel);

        self::assertEmpty($emailModel->getBody());
        self::assertEmpty($emailModel->getAttachments());
    }

    public function testHandleEmbeddedImagesDoesNothingWhenNoEmbeddedImages(): void
    {
        $body = 'sample_body';
        $emailModel = (new EmailModel())
            ->setBody($body);

        $this->embeddedImagesExtractor
            ->expects(self::once())
            ->method('extractEmbeddedImages')
            ->with($body)
            ->willReturn([]);

        $this->handler->handleEmbeddedImages($emailModel);

        self::assertEquals($body, $emailModel->getBody());
        self::assertEmpty($emailModel->getAttachments());
    }


    public function testHandleEmbeddedImages(): void
    {
        $emailModel = (new EmailModel())
            ->setBody('sample_body');

        $embeddedImage = new EmbeddedImage(
            'sample_content',
            'sample_filename',
            'sample-content/type',
            'sample_encoding'
        );
        $this->embeddedImagesExtractor
            ->expects(self::once())
            ->method('extractEmbeddedImages')
            ->willReturnCallback(static function (&$body) use ($embeddedImage) {
                $body .= '_changed';

                return [$embeddedImage];
            });

        $emailAttachmentContent = new EmailAttachmentContent();
        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachmentContent')
            ->with($embeddedImage->getEncodedContent(), $embeddedImage->getEncoding())
            ->willReturn($emailAttachmentContent);

        $emailAttachmentEntity = (new EmailAttachmentEntity())
            ->setEmbeddedContentId($embeddedImage->getFilename())
            ->setContent($emailAttachmentContent);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachment')
            ->with($embeddedImage->getFilename(), $embeddedImage->getContentType())
            ->willReturn($emailAttachmentEntity);

        $this->handler->handleEmbeddedImages($emailModel);

        self::assertEquals('sample_body_changed', $emailModel->getBody());
        self::assertEquals(
            [(new EmailAttachmentModel())->setEmailAttachment($emailAttachmentEntity)],
            $emailModel->getAttachments()->toArray()
        );
    }
}
