<?php

namespace App\Enums;

enum ApiStatus: string
{
    case QUEUED = 'queued';
    case OK = 'ok';
}
