<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class LoggedUserVariablesProvider implements SystemVariablesProviderInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param SecurityFacade      $securityFacade
     * @param NameFormatter       $nameFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter
    ) {
        $this->translator     = $translator;
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions()
    {
        return $this->getVariables(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableValues()
    {
        return $this->getVariables(true);
    }

    /**
     * @param bool $addValue FALSE for variable definitions; TRUE for variable values
     *
     * @return array
     */
    protected function getVariables($addValue)
    {
        $result = [];

        $user = $this->securityFacade->getLoggedUser();
        if ($user) {
            $this->addUserName($result, $user, $addValue);
            $this->addUserFirstName($result, $user, $addValue);
            $this->addUserLastName($result, $user, $addValue);
            $this->addUserFullName($result, $user, $addValue);
        }

        return $result;
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    protected function addUserName(array &$result, $user, $addValue)
    {
        if ($user instanceof UserInterface) {
            if ($addValue) {
                $val = $user->getUsername();
            } else {
                $val = [
                    'type' => 'string',
                    'name' => $this->translator->trans('oro.email.emailtemplate.user_name')
                ];
            }
            $result['userName'] = $val;
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    protected function addUserFirstName(array &$result, $user, $addValue)
    {
        if ($user instanceof FirstNameInterface) {
            if ($addValue) {
                $val = $user->getFirstName();
            } else {
                $val = [
                    'type' => 'string',
                    'name' => $this->translator->trans('oro.email.emailtemplate.user_first_name')
                ];
            }
            $result['userFirstName'] = $val;
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    protected function addUserLastName(array &$result, $user, $addValue)
    {
        if ($user instanceof LastNameInterface) {
            if ($addValue) {
                $val = $user->getLastName();
            } else {
                $val = [
                    'type' => 'string',
                    'name' => $this->translator->trans('oro.email.emailtemplate.user_last_name')
                ];
            }
            $result['userLastName'] = $val;
        }
    }

    /**
     * @param array  $result
     * @param object $user
     * @param bool   $addValue
     */
    protected function addUserFullName(array &$result, $user, $addValue)
    {
        if ($user instanceof FullNameInterface) {
            if ($addValue) {
                $val = $this->nameFormatter->format($user);
            } else {
                $val = [
                    'type' => 'string',
                    'name' => $this->translator->trans('oro.email.emailtemplate.user_full_name')
                ];
            }
            $result['userFullName'] = $val;
        }
    }
}
