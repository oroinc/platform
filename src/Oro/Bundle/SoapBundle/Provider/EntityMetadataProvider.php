<?php

namespace Oro\Bundle\SoapBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides metadata for EntityManagerAwareInterface objects.
 */
class EntityMetadataProvider implements MetadataProviderInterface
{
    /** @var ConfigManager */
    protected $cm;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(ConfigManager $cm, TranslatorInterface $translator)
    {
        $this->cm         = $cm;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($object)
    {
        $metadata = [];

        if ($object instanceof EntityManagerAwareInterface) {
            $classMetadata = $object->getManager()->getMetadata();
            if (null !== $classMetadata) {
                $entityFQCN = $classMetadata->name;
                $metadata['entity'] = [];

                $metadata['entity']['phpType'] = $entityFQCN;
                if ($this->cm->hasConfig($entityFQCN)) {
                    $config = $this->cm->getConfig(new EntityConfigId('entity', $entityFQCN));

                    $metadata['entity']['label']       = $this->translator->trans((string) $config->get('label'));
                    $metadata['entity']['pluralLabel'] = $this->translator->trans(
                        (string) $config->get('plural_label')
                    );
                    $metadata['entity']['description'] = $this->translator->trans((string) $config->get('description'));
                }
            }
        }

        return $metadata;
    }
}
