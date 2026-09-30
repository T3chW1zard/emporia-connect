<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Enums;

/**
 * AWS Cognito authentication flow used to log in.
 *
 * SRP is what the Emporia app uses and is the default. The plain password
 * flow is kept for user pools that allow it.
 */
enum AuthFlow: string
{
    case SRP = 'USER_SRP_AUTH';
    case PASSWORD = 'USER_PASSWORD_AUTH';
}
