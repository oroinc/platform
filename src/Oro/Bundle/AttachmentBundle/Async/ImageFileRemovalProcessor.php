<?php

namespace Oro\Bundle\AttachmentBundle\Async;

use Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Removes image files related to removed attachment related entities.
 */
class ImageFileRemovalProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private FileRemovalManagerInterface $imageRemovalManager;
    private LoggerInterface $logger;

    public function __construct(
        FileRemovalManagerInterface $imageRemovalManager,
        LoggerInterface $logger
    ) {
        $this->imageRemovalManager = $imageRemovalManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [AttachmentRemoveImageTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        foreach ($messageBody['images'] as $item) {
            try {
                $this->imageRemovalManager->removeFiles(
                    $this->getFile(
                        $item['id'],
                        $item['fileName'],
                        $item['originalFileName'],
                        $item['parentEntityClass']
                    )
                );
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf('Unable to remove image %s', $item['fileName']),
                    ['exception' => $e]
                );
            }
        }

        return self::ACK;
    }

    private function getFile(int $id, string $filename, string $originalFileName, string $parentEntityClass): File
    {
        $file = new FileModel();
        $file->setId($id);
        $file->setFilename($filename);
        $file->setOriginalFilename($originalFileName);
        $file->setParentEntityClass($parentEntityClass);
        $file->setExtension(pathinfo($filename)['extension']);

        return $file;
    }
}
