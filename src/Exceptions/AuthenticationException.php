<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Exceptions;

/**
 * Thrown when logging in to or refreshing tokens with AWS Cognito fails.
 */
class AuthenticationException extends EmporiaException {}
