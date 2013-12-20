<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TransCommand extends BaseCommand
{
    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:test-translations')
            ->setDescription('Get all labels, plural labels of configurable entities and modify them');
    }

    /**
     * Runs command
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var OroEntityManager $em */
        $em = $this->getConfigManager()->getEntityManager();

        $output->writeln($this->getDescription());

        /** @var ConfigManager $cm */
        $cm = $this->getConfigManager();

        /** @var EntityConfigId[] */
        $entityConfigIds = $cm->getProvider('entity')->getIds();
        $i = $n = 1;
        foreach ($entityConfigIds as $entityConfigId) {
            /** @var Config $entityConfig */
            $entityConfig = $cm->getProvider('entity')->getConfigById($entityConfigId);

            $enLabel  = $entityConfig->get('label');
            $this->addTranslation($em, $entityConfig->getId()->toString(), $enLabel);

            $enPlural = $entityConfig->get('plural_label');
            $this->addTranslation($em, $entityConfig->getId()->toString(), $enPlural);

            $output->writeln($i++ . ': ENTITY -> ' . $enLabel . ' - ' . $enPlural);

            /** @var Config[] $entityFields */
            $entityFields = $cm->getProvider('entity')->getConfigs($entityConfig->getId()->getClassName());
            foreach ($entityFields as $field) {
                $fLabel  = $field->get('label');
                $this->addTranslation($em, $field->getId()->getFieldName(), $fLabel);

                $output->writeln($n++ . ': ' . $field->getId()->getFieldName() . ' -> ' . $fLabel);
            }

            $output->writeln('-----------------------------------------');
        }

        $em->flush();

        $translatorCacheDir = $this->getContainer()->getParameter('kernel.cache_dir') . '/translations/';
        array_map(
            'unlink',
            glob($translatorCacheDir . 'catalogue.en' . '.*')
        );

        $output->writeln('Completed');
    }

    protected function addTranslation($em, $name, $transKey)
    {
        if (!$transKey) {
            return;
        }
        /** @var Translator $translator */
        $translator = $this->getContainer()->get('translator');

        $messages = $translator->getTranslations()['messages'];
        if (isset($messages[$transKey])) {
            $value = $messages[$transKey] . ' ' . rand(1001, 9999);
        } else {
            $value = $name . ' ' . rand(1001, 9999);
        }

        /** @var Translation $transValue */
        $transValue = new Translation();
        $transValue
            ->setKey($transKey)
            ->setValue($value)
            ->setDomain('messages')
            ->setLocale('en');

        $em->persist($transValue);
    }
}
