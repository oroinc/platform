<?php

namespace Oro\Bundle\DigitalAssetBundle\Migrations\Data;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstract fixture for loading digital assets.
 */
abstract class AbstractDigitalAssetFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    private ?PropertyAccessorInterface $propertyAccessor = null;

    private ?TokenStorageInterface $tokenStorage = null;

    private ?FileLocatorInterface $fileLocator = null;

    private ?FileManager $fileManager = null;

    abstract protected function getDataPath(): string;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;

        if ($this->container) {
            $this->propertyAccessor = $this->container->get('property_accessor');
            $this->tokenStorage = $this->container->get('security.token_storage');
            $this->fileLocator = $this->container->get('file_locator');
            $this->fileManager = $this->container->get('oro_attachment.file_manager');
        }
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);
        $previousToken = $this->tokenStorage->getToken();
        $this->setSecurityContext($this->tokenStorage, $user);

        foreach ($this->getData() as $referenceKey => $data) {
            $data['owner'] = $data['owner'] ?? $user;
            $data['organization'] = $data['organization'] ?? $user->getOrganization();
            $digitalAsset = $this->createDigitalAsset($data, $user);

            $manager->persist($digitalAsset);

            $this->setReference($referenceKey, $digitalAsset);
        }

        $manager->flush();

        $this->tokenStorage->setToken($previousToken);
    }

    protected function resolveReferences(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->resolveReferences($value);
            }

            if (is_string($value) && str_starts_with($value, '@')) {
                $value = substr($value, 1);
                $data[$key] = $this->getReference($value);
            }
        }

        return $data;
    }

    protected function getData(): array
    {
        $fileName = $this->fileLocator->locate($this->getDataPath());
        $fileName = str_replace('/', DIRECTORY_SEPARATOR, $fileName);

        return Yaml::parse(file_get_contents($fileName));
    }

    protected function setSecurityContext(TokenStorageInterface $tokenStorage, User $user): void
    {
        $tokenStorage->setToken(
            new UsernamePasswordOrganizationToken(
                $user,
                $user->getUsername(),
                'main',
                $user->getOrganization(),
                $user->getUserRoles()
            )
        );
    }

    protected function createDigitalAsset(array $data, User $user): DigitalAsset
    {
        $digitalAsset = new DigitalAsset();

        $imagePath = $this->fileLocator->locate($data['sourceFile']);
        $data['sourceFile'] = $this->fileManager
            ->createFileEntity($imagePath)
            ->setOwner($user);

        $data = $this->resolveReferences($data);

        foreach ($data as $key => $value) {
            if ($this->propertyAccessor->isWritable($digitalAsset, $key)) {
                $this->propertyAccessor->setValue($digitalAsset, $key, $value);
            }
        }

        return $digitalAsset;
    }
}
