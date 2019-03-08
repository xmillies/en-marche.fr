<?php

namespace AppBundle\Entity\Jecoute;

use AppBundle\Jecoute\SurveyTypeEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class NationalSurvey extends Survey
{
    public function getType(): string
    {
        return SurveyTypeEnum::NATIONAL;
    }
}
