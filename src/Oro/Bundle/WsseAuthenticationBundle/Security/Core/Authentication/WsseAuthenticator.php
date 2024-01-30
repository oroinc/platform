<?php

declare(strict_types=1);

namespace Oro\Bundle\WsseAuthenticationBundle\Security\Core\Authentication;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Security\FeatureDependAuthenticatorChecker;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface;
use Oro\Bundle\UserBundle\Security\UserApiKeyInterface;
use Oro\Bundle\WsseAuthenticationBundle\Exception\NonceExpiredException;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactoryInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * WSSE authenticator.
 */
class WsseAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    private string $dateFormat = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)'
    . '((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])'
    . '(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?0'
    . '0)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';

    public function __construct(
        private FeatureDependAuthenticatorChecker $featureDependAuthenticatorChecker,
        private TokenStorageInterface $tokenStorage,
        private WsseTokenFactoryInterface $wsseTokenFactory,
        private UserProviderInterface $userProvider,
        private AuthenticationEntryPointInterface $authenticationEntryPoint,
        private string $firewallName,
        private PasswordHasherInterface $passwordHasher,
        private AdapterInterface $nonceCache,
        private int $lifetime = 300,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (!$this->featureDependAuthenticatorChecker->isEnabled($this, $this->firewallName)) {
            return false;
        }
        if (!$request->headers->has('X-WSSE')) {
            return false;
        }
        $wsseHeaderData = $this->getHeaderData((string)$request->headers->get('X-WSSE'));
        if (empty($wsseHeaderData)) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        if (empty($this->firewallName)) {
            throw new \InvalidArgumentException('$firewallName must not be empty.');
        }
        $wsseHeaderData = $this->getHeaderData((string)$request->headers->get('X-WSSE'));
        $passport = new SelfValidatingPassport(
            new UserBadge($wsseHeaderData['Username'], [$this->userProvider, 'loadUserByIdentifier']),
            [new RememberMeBadge()]
        );
        $passport->setAttribute('nonce', $wsseHeaderData['Nonce']);
        $passport->setAttribute('created', $wsseHeaderData['Created']);
        $passport->setAttribute('passwordDigest', $wsseHeaderData['PasswordDigest']);

        $user = $passport->getUser();
        $secretApiKeys = $this->getSecretApiKeys($user);
        if (!$secretApiKeys instanceof Collection) {
            throw new AuthenticationException('WSSE authentication failed.');
        }
        $validUserApiKey = $this->getValidUserApiKey($passport, $secretApiKeys);
        if (null === $validUserApiKey) {
            throw new AuthenticationException('WSSE authentication failed.');
        }
        $passport->setAttribute('userApiKey', $validUserApiKey);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();
        $authenticatedToken = $this->wsseTokenFactory->create(
            $user,
            $firewallName,
            $user->getRoles()
        );
        $authenticatedToken->setOrganization($passport->getAttribute('userApiKey')->getOrganization());
        $this->tokenStorage->setToken($authenticatedToken);

        if ($this->nonceCache instanceof PruneableInterface) {
            $this->nonceCache->prune();
        }

        return $authenticatedToken;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->authenticationEntryPoint->start($request, $authException);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->start($request, $exception);
    }

    /**
     * If Username, PasswordDigest, Nonce and Created are set then it returns their value,
     * otherwise the method returns empty array.
     */
    private function getHeaderData(string $wsseHeader): array
    {
        $result = [];
        foreach (['Username', 'PasswordDigest', 'Nonce', 'Created'] as $key) {
            if ($value = $this->parseValue($wsseHeader, $key)) {
                $result[$key] = $value;
            }
        }

        return count($result) === 4 ? $result : [];
    }

    private function parseValue(string $wsseHeader, string $key): ?string
    {
        preg_match('/' . $key . '="([^"]+)"/', $wsseHeader, $matches);

        return $matches[1] ?? null;
    }

    private function getSecretApiKeys(AdvancedApiUserInterface $user): ?Collection
    {
        return $user->getApiKeys();
    }

    private function getValidUserApiKey(Passport $passport, Collection $secrets): ?UserApiKeyInterface
    {
        $currentIteration = 0;
        $nonce = $passport->getAttribute('nonce');
        $secretsCount = $secrets->count();

        /** @var UserApiKeyInterface $userApi */
        foreach ($secrets as $userApi) {
            $currentIteration++;
            $secret = $userApi->getApiKey();
            $isSecretValid = false;
            if ($secret) {
                $isSecretValid = $this->validateDigest(
                    $passport->getAttribute('passwordDigest'),
                    $nonce,
                    $passport->getAttribute('created'),
                    $secret,
                );
            }
            if ($isSecretValid) {
                if (!$userApi->getOrganization()->isEnabled()) {
                    throw new BadUserOrganizationException('Organization is not active.');
                }
                if (!$userApi->isEnabled()) {
                    throw new BadCredentialsException('Wrong API key.');
                }
            }
            // delete nonce from cache because user have another api keys
            if (!$isSecretValid && $secretsCount !== $currentIteration) {
                $this->nonceCache->delete($this->getNonceCacheKey($nonce));
            }
            if ($isSecretValid) {
                return $userApi;
            }
        }

        return null;
    }

    private function getNonceCacheKey(string $nonce): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_.]/', '_', $nonce);
        if (strlen($key) > 64) {
            $key = md5($key);
        }

        return $key;
    }

    private function validateDigest(string $digest, string $nonce, string $created, string $secret): bool
    {
        if (!$this->isFormattedCorrectly($created)) {
            throw new BadCredentialsException('Incorrectly formatted "created" in token.');
        }
        if ($this->isTokenFromFuture($created)) {
            throw new BadCredentialsException('Future token detected.');
        }
        if ($this->isTokenExpired($created)) {
            throw new CredentialsExpiredException('Token has expired.');
        }
        $nonceCacheKey = $this->getNonceCacheKey($nonce);
        $cacheItem = $this->nonceCache->getItem($nonceCacheKey);
        if ($cacheItem->isHit()) {
            throw new NonceExpiredException('Previously used nonce detected.');
        }
        $cacheItem
            ->set(strtotime($this->getCurrentTime()))
            ->expiresAfter($this->lifetime);
        $this->nonceCache->save($cacheItem);
        $expected = $this->passwordHasher->hash(
            sprintf('%s%s%s', base64_decode($nonce), $created, $secret)
        );

        return hash_equals($expected, $digest);
    }

    private function isFormattedCorrectly(string $created): bool
    {
        return (bool)preg_match($this->dateFormat, $created);
    }

    private function getCurrentTime(): string
    {
        return gmdate(DATE_ATOM);
    }

    private function isTokenFromFuture(string $created): bool
    {
        return strtotime($created) > strtotime($this->getCurrentTime());
    }

    private function isTokenExpired(string $created): bool
    {
        return !($this->lifetime === -1) && strtotime($this->getCurrentTime()) - strtotime($created) > $this->lifetime;
    }
}
