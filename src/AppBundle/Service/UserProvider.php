<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

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
     * @return null|object|UserInterface
     */
    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsername($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @return null|object|UserInterface
     */
    public function refreshUser(UserInterface $user)
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
    public function supportsClass($class)
    {
        return User::class === $class;
    }

    /**
     * Finds a user by username.
     *
     * @param $username
     * @return null|object
     */
    protected function findUserByUsername($username)
    {
        return $this->em->getRepository('AppBundle:User')->findOneBy(['username' => $username]);
    }
}
