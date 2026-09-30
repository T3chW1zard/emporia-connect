<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Exceptions;

/**
 * Thrown when the HTTP request could not be sent (DNS, connection, timeout...).
 */
class TransportException extends EmporiaException {}
