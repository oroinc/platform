<?php

namespace Oro\Bundle\TranslationBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\TranslationBundle\Entity\Language;

class LanguageVoter extends AbstractEntityVoter
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var array */
    protected $supportedAttributes = ['EDIT'];

    /** @var Language */
    protected $object;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        parent::__construct($doctrineHelper);

        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;

        return parent::vote($token, $object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isDefaultLanguage()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return bool
     */
    protected function isDefaultLanguage()
    {
        $defaultLanguage = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::LANGUAGE));

        return $defaultLanguage === $this->object->getCode();
    }
}
