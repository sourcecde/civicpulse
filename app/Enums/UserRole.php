<?php

namespace App\Enums;

enum UserRole: string
{
    case CITIZEN = 'CITIZEN';
    case OPERATOR = 'OPERATOR';
    case ADMIN = 'ADMIN';
}
