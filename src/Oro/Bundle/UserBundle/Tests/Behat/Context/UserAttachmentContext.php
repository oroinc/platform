<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\AttachmentBundle\Tests\Behat\Context\AttachmentImageContext;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class UserAttachmentContext extends AttachmentImageContext
{
    use KernelDictionary;

    private const USER_FIELD_AVATAR = 'avatar';

    /**
     * @Then /^(?:|I )should see avatar for user "(?P<username>[\w\s]+)"$/
     *
     * @param string $username
     */
    public function userAvatarIsGranted(string $username): void
    {
        $user = $this->getUser($username);
        $attachmentUrl = $this->getAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $resizeAttachmentUrl = $this->getResizeAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $filteredAttachmentUrl = $this->getFilteredAttachmentUrl($user, self::USER_FIELD_AVATAR);

        $this->assertResponseSuccess($this->downloadAttachment($attachmentUrl));
        $this->assertResponseSuccess($this->downloadAttachment($resizeAttachmentUrl));
        $this->assertResponseSuccess($this->downloadAttachment($filteredAttachmentUrl));
    }

    /**
     * @Then /^(?:|I )should not see avatar for user "(?P<userNameOrEmail>[\w\s]+)"$/
     *
     * @param string $username
     */
    public function userAvatarIsNotGranted(string $username): void
    {
        $user = $this->getUser($username);
        $attachmentUrl = $this->getAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $resizeAttachmentUrl = $this->getResizeAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $filteredAttachmentUrl = $this->getFilteredAttachmentUrl($user, self::USER_FIELD_AVATAR);

        $this->assertResponseFail($this->downloadAttachment($attachmentUrl));
        $this->assertResponseFail($this->downloadAttachment($resizeAttachmentUrl));
        $this->assertResponseFail($this->downloadAttachment($filteredAttachmentUrl));
    }

    /**
     * @param string $username
     *
     * @return User
     */
    private function getUser(string $username): User
    {
        /** @var UserManager $userManager */
        $userManager = $this->getContainer()->get('oro_user.manager');
        /** @var User $user */
        $user = $userManager->findUserByUsername($username);

        self::assertNotNull($user, sprintf('Could not find user with username "%s".', $username));
        $userManager->reloadUser($user);

        return $user;
    }
}
