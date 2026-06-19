<?php

namespace App\Enums;

enum NccdLevelEnum: string
{
    case QDTP          = 'QDTP';
    case Supplementary = 'Supplementary';
    case Substantial   = 'Substantial';
    case Extensive     = 'Extensive';
}
