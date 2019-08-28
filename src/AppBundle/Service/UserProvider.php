<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 * @package AppBundle\Service
 */
class UserProvider implements UserProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * UserProvider constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $username
     * @return object|UserInterface|null
     */
    public function loadUserByUsername($username): ?object
    {
        $user = $this->findUserByUsername($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @return object|UserInterface|null
     */
    public function refreshUser(UserInterface $user): ?object
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUsername();

        return $this->findUserByUsername($username);
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class): bool
    {
        return User::class === $class;
    }

    /**
     * Finds a user by username.
     *
     * @param string $username
     * @return object|null
     */
    protected function findUserByUsername(string $username): ?object
    {
        return $this->em->getRepository('AppBundle:User')->findOneBy(['username' => $username]);
    }
}
