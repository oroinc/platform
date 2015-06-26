<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EmailBundle\Model\EmailAttribute;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class RelatedEmailsProvider
{
    /** @var Registry */
    protected $registry;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param Registry $registry
     * @param ConfigProvider $entityConfigProvider
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        Registry $registry,
        ConfigProvider $entityConfigProvider,
        SecurityFacade $securityFacade
    ) {
        $this->registry = $registry;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param object $object
     * @param int $depth
     *
     * @return array
     */
    public function getEmails($object, $depth = 1)
    {
        $emails = [];

        if (!$depth || !$this->securityFacade->isGranted('VIEW', $object)) {
            return $emails;
        }

        $className = ClassUtils::getClass($object);

        $attributes = [];
        $metadata = $this->getMetadata($className);

        if (in_array('Oro\Bundle\EmailBundle\Model\EmailHolderInterface', class_implements($className))) {
            $attributes[] = new EmailAttribute('email');
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($metadata->fieldNames as $fieldName) {
            if (false !== stripos($fieldName, 'email')) {
                $attributes[] = new EmailAttribute($fieldName);
                continue;
            }

            if (!$this->entityConfigProvider->hasConfig($className, $fieldName)) {
                continue;
            }

            $fieldConfig = $this->entityConfigProvider->getConfig($className, $fieldName);
            if (!$fieldConfig->has('contact_information')) {
                continue;
            }

            if ($fieldConfig->get('contact_information') === 'email') {
                $attributes[] = new EmailAttribute($fieldName);
            }
        }

        foreach ($metadata->associationMappings as $name => $assoc) {
            if (in_array('Oro\Bundle\EmailBundle\Entity\EmailInterface', class_implements($assoc['targetEntity']))) {
                $attributes[] = new EmailAttribute($name, true);
            } else {
                if ($depth > 1) {
                    $assocObject = $propertyAccessor->getValue($object, $name);
                    if (!$assocObject instanceof \Traversable) {
                        if ($assocObject) {
                            $assocObject = [$assocObject];
                        } else {
                            $assocObject = [];
                        }
                    }
                    foreach ($assocObject as $obj) {
                        $emails = array_merge($emails, $this->getEmails($obj, $depth - 1));
                    }
                }
            }
        }

        foreach ($attributes as $attribute) {
            $value = $propertyAccessor->getValue($object, $attribute->getName());
            if (!$value instanceof \Traversable) {
                $value = [$value];
            }

            foreach ($value as $email) {
                if (is_scalar($email)) {
                    $emails[] = $email;
                } elseif ($email instanceof EmailInterface) {
                    $emails[] = $email->getEmail();
                }
            }
        }

        return array_combine($emails, $emails);
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function getMetadata($className)
    {
        $em = $this->registry->getManagerForClass($className);

        return $em->getClassMetadata($className);
    }
}
