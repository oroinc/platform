<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class WidgetUserSearchHandler extends UserSearchHandler
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     * @param AttachmentManager   $attachmentManager
     * @param string              $userEntityName
     * @param array               $properties
     */
    public function __construct(
        TranslatorInterface $translator,
        AttachmentManager $attachmentManager,
        $userEntityName,
        array $properties
    ) {
        parent::__construct($attachmentManager, $userEntityName, $properties);

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $result = parent::convertItems($items);

        $current = array_filter(
            $result,
            function ($item) {
                return $item[$this->idFieldName] === OwnerHelper::CURRENT_USER;
            }
        );
        if (empty($current)) {
            $current = [
                $this->idFieldName => OwnerHelper::CURRENT_USER,
                'fullName'         => $this->translator->trans('oro.user.dashboard.current_user'),
            ];
            array_unshift($result, $current);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        if ($this->idFieldName) {
            if (is_array($item)) {
                if ($item[$this->idFieldName] === OwnerHelper::CURRENT_USER) {
                    $current = [
                        $this->idFieldName => OwnerHelper::CURRENT_USER,
                        'fullName'         => $this->translator->trans('oro.user.dashboard.current_user'),
                    ];

                    return $current;
                }
            }
        }

        return parent::convertItem($item);
    }
}
