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
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Removes product image files and directories when removing product images
 */
class ImageRemoveMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const IMAGE_REMOVE_TOPIC = 'oro_attachment.remove_image';

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
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
