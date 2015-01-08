<?php

namespace Oro\Bundle\SoapBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

class EntityMetadataProvider implements MetadataProviderInterface
{
    /** @var ConfigManager */
    protected $cm;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ConfigManager       $cm
     * @param TranslatorInterface $translator
     */
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
            $entityFQCN         = $object->getManager()->getMetadata()->name;
            $metadata['entity'] = [];

            $metadata['entity']['phpType'] = $entityFQCN;
            if ($this->cm->hasConfig($entityFQCN)) {
                $config = $this->cm->getConfig(new EntityConfigId('entity', $entityFQCN));

                $metadata['entity']['label']       = $this->translator->trans($config->get('label'));
                $metadata['entity']['pluralLabel'] = $this->translator->trans($config->get('plural_label'));
                $metadata['entity']['description'] = $this->translator->trans($config->get('description'));
            }
        }

        return $metadata;
    }
}
