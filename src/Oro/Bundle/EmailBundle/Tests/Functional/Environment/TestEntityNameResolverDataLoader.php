<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;
    private EmailEntityBuilder $emailEntityBuilder;
    private TranslatorInterface $translator;

    public function __construct(
        TestEntityNameResolverDataLoaderInterface $innerDataLoader,
        EmailEntityBuilder $emailEntityBuilder,
        TranslatorInterface $translator
    ) {
        $this->innerDataLoader = $innerDataLoader;
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->translator = $translator;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (EmailUser::class === $entityClass) {
            $emailUser = $this->emailEntityBuilder->emailUser(
                'Test Email',
                'from@example.com',
                ['to@example.com'],
                new \DateTime('2023-05-01 02:10:00', new \DateTimeZone('UTC')),
                new \DateTime('2023-05-01 02:20:00', new \DateTimeZone('UTC')),
                new \DateTime('2023-05-01 02:00:00', new \DateTimeZone('UTC')),
                Email::HIGH_IMPORTANCE,
                null,
                null,
                $repository->getReference('user'),
                $repository->getReference('organization')
            );
            $emailUser->getEmail()->setMessageId('<email1@func-test>');
            $repository->setReference('emailUser', $emailUser);
            $this->emailEntityBuilder->getBatch()->persist($em);
            $em->flush();

            return ['emailUser'];
        }

        if (Mailbox::class === $entityClass) {
            $mailbox = new Mailbox();
            $mailbox->setOrganization($repository->getReference('organization'));
            $mailbox->setEmail('mailbox@example.com');
            $mailbox->setLabel('Test');
            $repository->setReference('mailbox', $mailbox);
            $em->persist($mailbox);
            $em->flush();

            return ['mailbox'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (EmailUser::class === $entityClass) {
            return 'Test Email';
        }
        if (Mailbox::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? 'Test'
                : 'Test ' . $this->translator->trans(
                    'oro.email.mailbox.entity_label',
                    [],
                    null,
                    $locale && str_starts_with($locale, 'Localization ')
                        ? substr($locale, \strlen('Localization '))
                        : $locale
                );
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
