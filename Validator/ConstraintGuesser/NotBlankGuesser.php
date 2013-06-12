<?php

namespace Pim\Bundle\ProductBundle\Validator\ConstraintGuesser;

use Symfony\Component\Validator\Constraints as Assert;
use Oro\Bundle\FlexibleEntityBundle\Form\Validator\ConstraintGuesserInterface;
use Oro\Bundle\FlexibleEntityBundle\Model\AbstractAttribute;

/**
 * @author    Gildas Quemener <gildas.quemener@gmail.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class NotBlankGuesser implements ConstraintGuesserInterface
{
    public function supportAttribute(AbstractAttribute $attribute)
    {
        return true;
    }

    public function guessConstraints(AbstractAttribute $attribute)
    {
        $constraints = array();

        if ($attribute->getRequired()) {
            $constraints[] = new Assert\NotBlank;
        }

        return $constraints;
    }
}

