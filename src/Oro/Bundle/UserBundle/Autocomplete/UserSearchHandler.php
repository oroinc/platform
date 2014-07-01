<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler;

class UserSearchHandler extends FullNameSearchHandler
{
    const IMAGINE_AVATAR_FILTER = 'avatar_med';

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @param AttachmentManager $attachmentManager
     * @param string $userEntityName
     * @param array $properties
     */
    public function __construct(AttachmentManager $attachmentManager, $userEntityName, array $properties)
    {
        $this->attachmentManager = $attachmentManager;
        parent::__construct($userEntityName, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($user)
    {
        $result = parent::convertItem($user);
        $result['avatar'] = null;

        $avatar = $this->getPropertyValue('avatar', $user);
        if ($avatar) {
            $result['avatar'] = $this->attachmentManager->getFilteredImageUrl(
                $avatar,
                self::IMAGINE_AVATAR_FILTER
            );
        }

        return $result;
    }
}
