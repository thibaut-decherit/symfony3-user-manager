<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class BreachedPassword
 * @Annotation
 */
class BreachedPassword extends Constraint
{
    public $message = '';

    public function validateBy()
    {
        return get_class($this) . 'Validator';
    }
}
