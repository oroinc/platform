<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides the following system variables related to the current logged in user for email templates:
 * * organizationName
 * * userName
 * * userFirstName
 * * userLastName
 * * userFullName
 * * userSignature
 */
class LoggedUserVariablesProvider implements SystemVariablesProviderInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var EntityNameResolver */
    private $entityNameResolver;

    /** @var ConfigManager */
    private $configManager;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    public function __construct(
        TranslatorInterface $translator,
        TokenAccessorInterface $tokenAccessor,
        EntityNameResolver $entityNameResolver,
        ConfigManager $configManager,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->translator = $translator;
        $this->tokenAccessor = $tokenAccessor;
        $this->entityNameResolver = $entityNameResolver;
        $this->configManager = $configManager;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions(): array
    {
        return $this->getVariables(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableValues(): array
    {
        return $this->getVariables(true);
    }

    /**
     * @param bool $addValue FALSE for variable definitions; TRUE for variable values
     *
     * @return array
     */
    private function getVariables(bool $addValue): array
    {
        $result = [];

        $organization = $this->tokenAccessor->getOrganization();
        $user = $this->tokenAccessor->getUser();

        $this->addOrganizationName($result, $organization, $addValue);
        $this->addUserName($result, $user, $addValue);
        $this->addUserFirstName($result, $user, $addValue);
        $this->addUserLastName($result, $user, $addValue);
        $this->addUserFullName($result, $user, $addValue);
        $this->addUserSignature($result, $user, $addValue);

        return $result;
    }

    /**
     * @param array  $result
     * @param object $organization
     * @param bool   $addValue
     */
    private function addOrganizationName(array &$result, $organization, bool $addValue): void
    {
        if ($organization instanceof Organization) {
            if ($addValue) {
                $val = $organization->getName();
            } else {
                $val = [
                    'type'  => 'string',
                    'label' => $this->translator->trans('oro.email.emailtemplate.organization_name')
                ];
            }
            $result['organizationName'] = $val;
        } elseif ($addValue) {
            $result['organizationName'] = '';
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    private function addUserName(array &$result, $user, bool $addValue): void
    {
        if ($user instanceof UserInterface) {
            if ($addValue) {
                $val = $user->getUsername();
            } else {
                $val = [
                    'type'  => 'string',
                    'label' => $this->translator->trans('oro.email.emailtemplate.user_name')
                ];
            }
            $result['userName'] = $val;
        } elseif ($addValue) {
            $result['userName'] = '';
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    private function addUserFirstName(array &$result, $user, bool $addValue): void
    {
        if ($user instanceof FirstNameInterface) {
            if ($addValue) {
                $val = $user->getFirstName();
            } else {
                $val = [
                    'type'  => 'string',
                    'label' => $this->translator->trans('oro.email.emailtemplate.user_first_name')
                ];
            }
            $result['userFirstName'] = $val;
        } elseif ($addValue) {
            $result['userFirstName'] = '';
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    private function addUserLastName(array &$result, $user, bool $addValue): void
    {
        if ($user instanceof LastNameInterface) {
            if ($addValue) {
                $val = $user->getLastName();
            } else {
                $val = [
                    'type'  => 'string',
                    'label' => $this->translator->trans('oro.email.emailtemplate.user_last_name')
                ];
            }
            $result['userLastName'] = $val;
        } elseif ($addValue) {
            $result['userLastName'] = '';
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    private function addUserFullName(array &$result, $user, bool $addValue): void
    {
        if ($user instanceof FullNameInterface) {
            if ($addValue) {
                $val = $this->entityNameResolver->getName($user);
            } else {
                $val = [
                    'type'  => 'string',
                    'label' => $this->translator->trans('oro.email.emailtemplate.user_full_name')
                ];
            }
            $result['userFullName'] = $val;
        } elseif ($addValue) {
            $result['userFullName'] = '';
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    private function addUserSignature(array &$result, $user, bool $addValue): void
    {
        if (is_object($user)) {
            if ($addValue) {
                $val = $this->htmlTagHelper->sanitize($this->configManager->get('oro_email.signature'));
            } else {
                $val = [
                    'type'  => 'string',
                    'label' => $this->translator->trans('oro.email.emailtemplate.siganture'),
                    'filter' => 'oro_html_strip_tags',
                ];
            }
            $result['userSignature'] = $val;
        } elseif ($addValue) {
            $result['userSignature'] = '';
        }
    }
}
