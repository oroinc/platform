<?php

namespace Oro\Bundle\AttachmentBundle\Async;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        return [Topics::ATTACHMENT_REMOVE_IMAGE];
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
                try {
                    $imageData = $this->getOptionsResolver()->resolve($imageData);
                } catch (OptionsResolverException $e) {
                    $this->logger->warning(
                        'Unable to remove image',
                        ['exception' => $e, 'imageData' => json_encode($imageData)]
                    );

                    continue;
                }

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

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([
            'id',
            'fileName',
            'originalFileName',
            'parentEntityClass',
        ]);
        $resolver->setNormalizer('originalFileName', function (Options $options, $value) {
            return $value ?: $options['fileName'];
        });
        $resolver->setAllowedTypes('id', ['int']);
        $resolver->setAllowedTypes('fileName', ['string']);
        $resolver->setAllowedTypes('originalFileName', ['string', 'null']);
        $resolver->setAllowedTypes('parentEntityClass', ['string']);

        return $resolver;
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
