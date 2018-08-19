<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 *
 * @UniqueEntity(
 *     fields={"email"},
 *     message="form_errors.unique_email"
 * )
 * @UniqueEntity(
 *     fields={"username"},
 *     message="form_errors.unique_username"
 * )
 */
class User implements UserInterface, AdvancedUserInterface, EquatableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank"
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length"
     * )
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     *
     * @Assert\Regex(
     *     pattern = "/^(?=\D*\d)(?=[^a-z]*[a-z])(?=[^A-Z]*[A-Z])[\w~@#$%^&*+=`|{}:;!.?""''()\[\]-]{8,50}$/",
     *     message = "form_errors.password_strength",
     * )
     *
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank"
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length"
     * )
     * @Assert\Email(
     *      message = "form_errors.valid_email",
     *      checkMX = true
     * )
     */
    private $email;

    /**
     * ORM mapping not needed if password hash algorithm generates it's own salt (e.g bcrypt)
     *
     * @var string
     *
     */
    private $salt;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array", nullable=true)
     */
    private $roles;

    /**
     * @var bool
     *
     * @ORM\Column(name="hasBeenActivated", type="boolean")
     */
    private $hasBeenActivated;

    /**
     * @var string
     *
     * @ORM\Column(name="accountToken", type="string", length=255, nullable=true, unique=true)
     */
    private $accountToken;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->hasBeenActivated = false;
        $this->accountToken = sha1(random_bytes(50));
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set hasBeenActivated
     *
     * @param bool $hasBeenActivated
     *
     * @return User
     */
    public function setHasBeenActivated($hasBeenActivated)
    {
        $this->hasBeenActivated = $hasBeenActivated;

        return $this;
    }

    /**
     * Get hasBeenActivated
     *
     * @return bool
     */
    public function getHasBeenActivated()
    {
        return $this->hasBeenActivated;
    }

    /**
     * Set accountToken
     *
     * @param string $accountToken
     *
     * @return User
     */
    public function setAccountToken($accountToken)
    {
        $this->accountToken = $accountToken;

        return $this;
    }

    /**
     * Get accountToken
     *
     * @return string
     */
    public function getAccountToken()
    {
        return $this->accountToken;
    }

    public function eraseCredentials()
    {
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user has activated his/her account.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->hasBeenActivated;
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @return $this
     */
    public function activateAccount()
    {
        $this->setHasBeenActivated(true);
        $this->setAccountToken(null);

        return $this;
    }
}
