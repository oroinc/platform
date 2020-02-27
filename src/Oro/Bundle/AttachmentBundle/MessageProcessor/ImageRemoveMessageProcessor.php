<?php

namespace Oro\Bundle\AttachmentBundle\MessageProcessor;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Removes product image files and directories when removing product images
 */
class ImageRemoveMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const IMAGE_REMOVE_TOPIC = 'oro_attachment.remove_image';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ImageRemovalManagerInterface
     */
    private $imageRemovalManager;

    /**
     * @param LoggerInterface $logger
     * @param ImageRemovalManagerInterface $imageRemovalManager
     */
    public function __construct(
        LoggerInterface $logger,
        ImageRemovalManagerInterface $imageRemovalManager
    ) {
        $this->logger = $logger;
        $this->imageRemovalManager = $imageRemovalManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [self::IMAGE_REMOVE_TOPIC];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        //array<array{id: int, fileName: string, originalFileName: string}}>
        $images = JSON::decode($message->getBody());
        foreach ($images as $imageData) {
            try {
                $fileId = $imageData['id'];
                $fileName = $imageData['fileName'];
                $originalFileName = $imageData['originalFileName'];
                $parentEntityClass = $imageData['parentEntityClass'];

                /** @var File $file */
                $file = $this->getFile($fileId, $fileName, $originalFileName, $parentEntityClass);
                $this->imageRemovalManager->removeImageWithVariants($file);
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf('Unable to remove image %s', $fileName),
                    ['exception' => $e]
                );
            }
        }

        return self::ACK;
    }

    /**
     * @param int $id
     * @param string $filename
     * @param string $originalFileName
     * @param string $parentEntityClass
     * @return File
     */
    private function getFile(int $id, string $filename, string $originalFileName, string $parentEntityClass)
    {
        $file = new FileModel();
        $file->setId($id);
        $file->setFilename($filename);
        $file->setOriginalFilename($originalFileName);
        $file->setParentEntityClass($parentEntityClass);

        return $file;
    }
}
