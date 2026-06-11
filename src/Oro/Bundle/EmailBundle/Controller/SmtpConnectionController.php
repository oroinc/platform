<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Provider\SmtpSettingsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The controller to check SMTP connection.
 */
class SmtpConnectionController extends AbstractController
{
    #[Route(path: '/check-smtp-connection', name: 'oro_email_check_smtp_connection', methods: ['POST'])]
    #[CsrfProtection]
    public function checkSmtpConnectionAction(Request $request): JsonResponse
    {
        $scopeClass = $this->getScopeClass($request);
        $scopeIdentifier = $this->getScopeIdentifier($scopeClass, $this->getScopeId($request));
        if (!$this->isConnectionCheckingGranted($scopeClass, $scopeIdentifier)) {
            throw $this->createAccessDeniedException();
        }

        $error = null;
        $this->getSmtpSettingsChecker()->checkConnection(
            SmtpSettingsFactory::createFromRequest($request),
            $error
        );

        return new JsonResponse($error ?? '');
    }

    #[Route(path: '/check-saved-smtp-connection', name: 'oro_email_check_saved_smtp_connection', methods: ['POST'])]
    #[CsrfProtection]
    public function checkSavedSmtpConnectionAction(Request $request): JsonResponse
    {
        $scopeClass = $this->getScopeClass($request);
        $scopeIdentifier = $this->getScopeIdentifier($scopeClass, $this->getScopeId($request));
        if (!$this->isConnectionCheckingGranted($scopeClass, $scopeIdentifier)) {
            throw $this->createAccessDeniedException();
        }

        $error = null;
        $this->getSmtpSettingsChecker()->checkConnection(
            $this->getSmtpSettingsProvider()->getSmtpSettings($scopeIdentifier),
            $error
        );

        return new JsonResponse($error ?? '');
    }

    private function getScopeClass(Request $request): ?string
    {
        $scopeClass = $request->query->get('scopeClass');
        if (!$scopeClass) {
            return null;
        }

        return $scopeClass;
    }

    private function getScopeId(Request $request): ?int
    {
        $scopeId = $request->query->get('scopeId');
        if (!$scopeId || !is_numeric($scopeId)) {
            return null;
        }

        return (int)$scopeId;
    }

    private function getScopeIdentifier(?string $scopeClass, ?int $scopeId): ?object
    {
        if (null === $scopeClass || null === $scopeId) {
            return null;
        }

        return $this->getEntityManager($scopeClass)->find($scopeClass, $scopeId);
    }

    private function isConnectionCheckingGranted(?string $scopeClass, ?object $scopeIdentifier): bool
    {
        if (null === $scopeClass) {
            return $this->getAuthorizationChecker()->isGranted('oro_config_system');
        }
        if ($scopeIdentifier instanceof Organization) {
            return $this->getAuthorizationChecker()->isGranted(BasicPermission::EDIT, $scopeIdentifier);
        }
        throw new \LogicException(\sprintf('Unsupported scope: %s.', $scopeClass));
    }

    private function getSmtpSettingsChecker(): SmtpSettingsChecker
    {
        return $this->container->get(SmtpSettingsChecker::class);
    }

    private function getSmtpSettingsProvider(): SmtpSettingsProviderInterface
    {
        return $this->container->get(SmtpSettingsProviderInterface::class);
    }

    private function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->container->get(AuthorizationCheckerInterface::class);
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return $this->container->get(ManagerRegistry::class)->getManagerForClass($entityClass);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            SmtpSettingsChecker::class,
            SmtpSettingsProviderInterface::class,
            AuthorizationCheckerInterface::class,
            ManagerRegistry::class
        ]);
    }
}
