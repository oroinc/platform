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
    /** @var FileRemovalManagerInterface */
    private $imageRemovalManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        FileRemovalManagerInterface $imageRemovalManager,
        LoggerInterface $logger
    ) {
        $this->imageRemovalManager = $imageRemovalManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [AttachmentRemoveImageTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        //array<array{id: int, fileName: string, originalFileName: string}}>
        $messageBody = $message->getBody();
        foreach ($messageBody['images'] as $imageData) {
            try {
                $fileId = $imageData['id'];
                $fileName = $imageData['fileName'];
                $originalFileName = $imageData['originalFileName'];
                $parentEntityClass = $imageData['parentEntityClass'];

                /** @var File $file */
                $file = $this->getFile($fileId, $fileName, $originalFileName, $parentEntityClass);
                $this->imageRemovalManager->removeFiles($file);
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf('Unable to remove image %s', $fileName),
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
