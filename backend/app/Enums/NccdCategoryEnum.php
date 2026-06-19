<?php

namespace App\Enums;

enum NccdCategoryEnum: string
{
    case Cognitive       = 'Cognitive';
    case Physical        = 'Physical';
    case Sensory         = 'Sensory';
    case SocialEmotional = 'Social/Emotional';
}
