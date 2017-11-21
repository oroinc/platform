<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ContactInformationEmailsProvider
{
    /** @var Registry */
    protected $registry;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param Registry            $registry
     * @param ConfigProvider      $configProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Registry $registry,
        ConfigProvider $configProvider,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->configProvider = $configProvider;
        $this->translator = $translator;
    }

    /**
     * @param string $entityName
     *
     * @return array
     */
    public function getRecipients($entityName)
    {
        $emailFields = [];
        $metadata = $this->getMetadata($entityName);

        foreach ($metadata->fieldNames as $fieldName) {
            $label = $fieldName;

            if ($this->isContactInfoEmailType($metadata->name, $fieldName, $label)) {
                $emailFields[$fieldName] = $label;
            }
        }

        return $emailFields;
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function getMetadata($className)
    {
        /** @var $em EntityManager */
        $em = $this->registry->getManagerForClass($className);

        return $em->getClassMetadata($className);
    }


    /**
     * @param string      $className
     * @param string|null $column
     * @param string      $label
     *
     * @return bool
     */
    protected function isContactInfoEmailType($className, $column, &$label = '')
    {
        if ($this->configProvider->hasConfig($className, $column)) {
            $fieldConfig = $this->configProvider->getConfig($className, $column);
            $label = $this->translator->trans($fieldConfig->get('label'));

            return 'email' === $fieldConfig->get('contact_information') ;
        }

        return false;
    }
}
