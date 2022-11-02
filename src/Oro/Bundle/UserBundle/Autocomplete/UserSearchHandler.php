<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler;

/**
 * Autocomplete search handler for users.
 */
class UserSearchHandler extends FullNameSearchHandler
{
    const IMAGINE_AVATAR_FILTER = 'avatar_xsmall';

    /** @var PictureSourcesProviderInterface */
    protected $pictureSourcesProvider;

    /**
     * @param PictureSourcesProviderInterface $pictureSourcesProvider
     * @param string $userEntityName
     * @param array $properties
     */
    public function __construct(
        PictureSourcesProviderInterface $pictureSourcesProvider,
        $userEntityName,
        array $properties
    ) {
        $this->pictureSourcesProvider = $pictureSourcesProvider;
        parent::__construct($userEntityName, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($user)
    {
        $result = parent::convertItem($user);
        $result['avatar'] = $this->pictureSourcesProvider->getFilteredPictureSources(
            $this->getPropertyValue('avatar', $user),
            self::IMAGINE_AVATAR_FILTER
        );

        return $result;
    }
}
