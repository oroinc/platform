<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tests\Behat\Context\AttachmentContext;
use Oro\Bundle\AttachmentBundle\Tests\Behat\Context\AttachmentImageContext;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class UserAttachmentContext extends AttachmentContext
{
    private const USER_FIELD_AVATAR = 'avatar';

    /** @var AttachmentImageContext */
    private $attachmentImageContext;

    private BaseUserManager $userManager;

    public function __construct(AttachmentManager $attachmentManager, BaseUserManager $userManager)
    {
        $this->userManager = $userManager;

        parent::__construct($attachmentManager);
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->attachmentImageContext = $environment->getContext(AttachmentImageContext::class);
    }

    /**
     * @Then /^(?:|I )should see avatar for user "(?P<username>[\w\s]+)"$/
     */
    public function userAvatarIsGranted(string $username): void
    {
        $user = $this->getUser($username);
        $attachmentUrl = $this->attachmentImageContext->getAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $resizeAttachmentUrl = $this->attachmentImageContext->getResizeAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $filteredAttachmentUrl = $this->attachmentImageContext->getFilteredAttachmentUrl(
            $user,
            self::USER_FIELD_AVATAR
        );

        $this->assertResponseSuccess($this->attachmentImageContext->downloadAttachment($attachmentUrl));
        $this->assertResponseSuccess($this->downloadAttachment($resizeAttachmentUrl));
        $this->assertResponseSuccess($this->downloadAttachment($filteredAttachmentUrl));
    }

    /**
     * @Then /^(?:|I )should not see avatar for user "(?P<userNameOrEmail>[\w\s]+)"$/
     */
    public function userAvatarIsNotGranted(string $username): void
    {
        $user = $this->getUser($username);
        $attachmentUrl = $this->getAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $resizeAttachmentUrl = $this->attachmentImageContext->getResizeAttachmentUrl($user, self::USER_FIELD_AVATAR);
        $filteredAttachmentUrl = $this->attachmentImageContext->getFilteredAttachmentUrl(
            $user,
            self::USER_FIELD_AVATAR
        );

        $this->assertResponseFail($this->downloadAttachment($attachmentUrl));
        $this->assertResponseFail($this->downloadAttachment($resizeAttachmentUrl));
        $this->assertResponseFail($this->downloadAttachment($filteredAttachmentUrl));
    }

    private function getUser(string $username): User
    {
        /** @var User $user */
        $user = $this->userManager->findUserByUsername($username);

        self::assertNotNull($user, sprintf('Could not find user with username "%s".', $username));
        $this->userManager->reloadUser($user);

        return $user;
    }

    protected function assertResponseSuccess(ResponseInterface $response): void
    {
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($this->getAttachmentManager()->isImageType($response->getHeader('Content-Type')[0]));
    }

    protected function assertResponseFail(ResponseInterface $response): void
    {
        self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_FORBIDDEN]);
        static::assertStringContainsString('text/html', $response->getHeader('Content-Type')[0]);
    }
}
