<?php

namespace AppBundle\Entity;

use AppBundle\Model\AbstractUser;
use Doctrine\ORM\Mapping as ORM;


/**
 * Class User
 * @package AppBundle\Entity
 *
 * Class extending Model\AbstractUser which contains everything required to manage an user account so you can focus on
 * business and project specific attributes and functions.
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class User extends AbstractUser
{

}
