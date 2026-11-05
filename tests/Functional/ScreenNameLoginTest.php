<?php

declare(strict_types=1);

/*
 * This file is part of the Dowsing Machine project.
 *
 * (c) Dowsing Machine contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @see docs/features.md F1.9 — Login with screen name or email
 */
class ScreenNameLoginTest extends AbstractFunctionalTest
{
    public function testLoginWithScreenName(): void
    {
        $this->submitLogin('Admin', 'password');

        self::assertResponseRedirects();
        self::assertStringNotContainsString('/login', (string) $this->client->getResponse()->headers->get('Location'));
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testLoginWithScreenNameAndWrongPasswordFails(): void
    {
        $this->submitLogin('Admin', 'wrong-password');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger');
    }

    public function testAnonymizedUserCannotBeLoadedByEmailOrScreenName(): void
    {
        $repository = $this->userRepository();
        $user = $repository->findOneBy(['email' => 'borrower@example.com']);
        self::assertInstanceOf(User::class, $user);
        $screenName = $user->getScreenName();

        $user->setIsAnonymized(true);
        $this->entityManager()->flush();

        self::assertNull($repository->loadUserByIdentifier('borrower@example.com'));
        self::assertNull($repository->loadUserByIdentifier($screenName));
    }

    public function testRegistrationRejectsScreenNameContainingAt(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'new-user@example.com',
            'registration_form[screenName]' => 'admin@example.com',
            'registration_form[firstName]' => 'New',
            'registration_form[lastName]' => 'Player',
            'registration_form[plainPassword][first]' => 'SecurePass123!',
            'registration_form[plainPassword][second]' => 'SecurePass123!',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'A screen name cannot contain “@”.');
        self::assertNull($this->userRepository()->findOneBy(['email' => 'new-user@example.com']));
    }

    private function submitLogin(string $identifier, string $password): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Login', [
            '_email' => $identifier,
            '_password' => $password,
        ]);
    }

    private function userRepository(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = static::getContainer()->get(UserRepository::class);

        return $repository;
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        return $entityManager;
    }
}
