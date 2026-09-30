<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;

/**
 * Supplies valid Cognito tokens to the transport layer.
 */
interface TokenProviderContract
{
    /**
     * Current, non-expired token set. Logs in or refreshes when needed.
     *
     * @throws AuthenticationException
     */
    public function tokens(): TokenSet;

    /**
     * Force a refresh, e.g. after the API answered 401.
     *
     * @throws AuthenticationException
     */
    public function refresh(): TokenSet;
}
