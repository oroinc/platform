<?php

namespace Oro\Bundle\DataAuditBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Combine author name and impersonation for audit data grid
 */
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
     * @param array $node
     *
     * @return callable
     */
    public function getAuthor($gridName, $keyName, $node)
    {
        return function (ResultRecord $record) {
            $author = $record->getValue('author');

            /** @var Impersonation $impersonation */
            $impersonation = $record->getValue('impersonation');

            if ($impersonation) {
                $impersonationTranslation = $this->translator->trans(
                    'oro.dataaudit.datagrid.author_impersonation',
                    ['%ipAddress%' => $impersonation->getIpAddress(), '%token%' => $impersonation->getToken()]
                );

                $author = trim(sprintf('%s %s', $author, $impersonationTranslation), ' ');
            }

            return $author;
        };
    }
}
