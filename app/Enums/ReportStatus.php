<?php

namespace App\Enums;

enum ReportStatus: string
{
    case OPEN = 'OPEN';
    case ASSIGNED = 'ASSIGNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
}
