<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Validator\Constraints as CustomAssert;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 *
 * @UniqueEntity(
 *     fields={"email"},
 *     message="form_errors.unique_email",
 *     groups={"Registration", "User_Information"}
 * )
 * @UniqueEntity(
 *     fields={"username"},
 *     message="form_errors.unique_username",
 *     groups={"Registration", "User_Information"}
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
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *     groups={"Registration", "User_Information"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length",
     *      groups={"Registration", "User_Information"}
     * )
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * Used for model validation. Must not be persisted. Needed to avoid raw password overwriting
     * current user $user->getPassword() when being tested by UserPasswordValidator
     *
     * @var string
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     * )
     * @Assert\Regex(
     *     pattern = "/^(?=\D*\d)(?=[^a-z]*[a-z])(?=[^A-Z]*[A-Z])[\w~@#$%^&*+=`|{}:;!.?""''()\[\]-]{8,50}$/",
     *     message = "form_errors.password_strength",
     * )
     * @CustomAssert\BreachedPassword()
     *
     */
    protected $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *      groups={"Registration", "User_Information"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length",
     *      groups={"Registration", "User_Information"}
     * )
     * @Assert\Email(
     *      message = "form_errors.valid_email",
     *      checkMX = true,
     *      groups={"Registration", "User_Information"}
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
     * @ORM\Column(type="array", nullable=true)
     */
    private $roles;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $registeredAt;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $activated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $activationToken;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $passwordResetToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $passwordResetRequestedAt;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->registeredAt = new \DateTime();
        $this->activated = false;
        $this->activationToken = $this->generateSecureToken();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return User
     */
    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     * @return User
     */
    public function setPlainPassword(string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     * @return User
     */
    public function setSalt(string $salt): User
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getRegisteredAt(): ?\DateTime
    {
        return $this->registeredAt;
    }

    /**
     * @param \DateTime $registeredAt
     * @return User
     */
    public function setRegisteredAt(\DateTime $registeredAt): User
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    /**
     * @param bool $activated
     * @return User
     */
    public function setActivated(bool $activated): User
    {
        $this->activated = $activated;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    /**
     * @param string $activationToken
     * @return User
     */
    public function setActivationToken(string $activationToken): User
    {
        $this->activationToken = $activationToken;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    /**
     * @param null|string $passwordResetToken
     * @return User
     */
    public function setPasswordResetToken(?string $passwordResetToken): User
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPasswordResetRequestedAt(): ?\DateTime
    {
        return $this->passwordResetRequestedAt;
    }

    /**
     * @param \DateTime|null $passwordResetRequestedAt
     * @return User
     */
    public function setPasswordResetRequestedAt(?\DateTime $passwordResetRequestedAt): User
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;
        return $this;
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
    public function isAccountNonExpired(): bool
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
    public function isAccountNonLocked(): bool
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
    public function isCredentialsNonExpired(): bool
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
    public function isEnabled(): bool
    {
        return $this->activated;
    }

    /**
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
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
     * @return string
     * @throws \Exception
     */
    public function generateSecureToken(): string
    {
        return bin2hex(random_bytes(50));
    }

    /**
     * @param int $passwordResetRequestRetryDelay
     * @return bool
     */
    public function isPasswordResetRequestRetryDelayExpired(int $passwordResetRequestRetryDelay): bool
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetRequestRetryDelay < time();
    }

    /**
     * @param int $passwordResetTokenLifetime
     * @return bool
     */
    public function isPasswordResetTokenExpired(int $passwordResetTokenLifetime): bool
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetTokenLifetime < time();
    }
}
