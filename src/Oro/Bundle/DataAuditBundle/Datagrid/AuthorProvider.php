<?php

namespace Oro\Bundle\DataAuditBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Symfony\Component\Translation\TranslatorInterface;

class AuthorProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $gridName
     * @param string $keyName
     * @param array  $node
     *
     * @return callable
     */
    public function getAuthor($gridName, $keyName, $node)
    {
        return function (ResultRecord $record) {
            $user = $record->getValue('user');
            if (!$user) {
                return $record->getValue('owner_description');
            }
            $author = sprintf(
                '%s %s - %s',
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail()
            );

            $impersonation = $record->getValue('impersonation');
            if ($impersonation) {
                $author .= $this->translator->trans(
                    'oro.dataaudit.datagrid.author_impersonation',
                    ['%ipAddress%' => $impersonation->getIpAddress()]
                );
            }

            return $author;
        };
    }
}
