<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth;

use DateTimeInterface;
use DateTimeZone;
use phpseclib3\Math\BigInteger;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;

/**
 * AWS Cognito Secure Remote Password (USER_SRP_AUTH) helper.
 *
 * A direct port of pycognito's aws_srp.AWSSRP, which PyEmVue uses to log in.
 *
 * @see https://github.com/NabuCasa/pycognito/blob/master/pycognito/aws_srp.py
 */
final readonly class Srp
{
    private const N_HEX = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD1'
        .'29024E088A67CC74020BBEA63B139B22514A08798E3404DD'
        .'EF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245'
        .'E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED'
        .'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3D'
        .'C2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F'
        .'83655D23DCA3AD961C62F356208552BB9ED529077096966D'
        .'670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B'
        .'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9'
        .'DE2BCBF6955817183995497CEA956AE515D2261898FA0510'
        .'15728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64'
        .'ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7'
        .'ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6B'
        .'F12FFA06D98A0864D87602733EC86A64521F2B18177B200C'
        .'BBE117577A615D6C770988C0BAD946E208E24FA074E5AB31'
        .'43DB5BFCE0FD108E4B82D120A93AD2CAFFFFFFFFFFFFFFFF';

    private const G_HEX = '2';

    private const INFO_BITS = 'Caldera Derived Key';

    private BigInteger $bigN;

    private BigInteger $g;

    private BigInteger $k;

    private BigInteger $smallA;

    private BigInteger $largeA;

    /**
     * @param  string  $poolName  the part of the user pool id after the underscore, e.g. "ghlOXVLi1"
     * @param  string|null  $smallAHex  fixed private value, for tests only
     */
    public function __construct(private string $poolName, ?string $smallAHex = null)
    {
        $this->bigN = new BigInteger(self::N_HEX, 16);
        $this->g = new BigInteger(self::G_HEX, 16);
        $this->k = new BigInteger($this->hexHash('00'.self::N_HEX.'0'.self::G_HEX), 16);

        $random = new BigInteger($smallAHex ?? bin2hex(random_bytes(128)), 16);
        [, $this->smallA] = $random->divide($this->bigN);
        $this->largeA = $this->g->modPow($this->smallA, $this->bigN);

        if ($this->largeA->equals(new BigInteger(0))) {
            throw new AuthenticationException('SRP safety check for A failed.');
        }
    }

    /**
     * Public value A, sent as SRP_A in InitiateAuth.
     */
    public function largeAHex(): string
    {
        return $this->longToHex($this->largeA);
    }

    /**
     * Answer a PASSWORD_VERIFIER challenge.
     *
     * @param  array<array-key, mixed>  $challengeParameters  ChallengeParameters returned by InitiateAuth
     * @return array{TIMESTAMP: string, USERNAME: string, PASSWORD_CLAIM_SECRET_BLOCK: string, PASSWORD_CLAIM_SIGNATURE: string}
     */
    public function processChallenge(array $challengeParameters, string $username, string $password, DateTimeInterface $now): array
    {
        $userIdForSrp = $this->param($challengeParameters, 'USER_ID_FOR_SRP');
        $saltHex = $this->param($challengeParameters, 'SALT');
        $srpBHex = $this->param($challengeParameters, 'SRP_B');
        $secretBlock = $this->param($challengeParameters, 'SECRET_BLOCK');
        $internalUsername = is_string($challengeParameters['USERNAME'] ?? null) ? $challengeParameters['USERNAME'] : $username;

        $timestamp = self::timestamp($now);
        $hkdf = $this->passwordAuthenticationKey($userIdForSrp, $password, new BigInteger($srpBHex, 16), $saltHex);

        $secretBlockBytes = base64_decode($secretBlock, true);

        if ($secretBlockBytes === false) {
            throw new AuthenticationException('Cognito returned an invalid SRP secret block.');
        }

        $message = $this->poolName.$userIdForSrp.$secretBlockBytes.$timestamp;

        return [
            'TIMESTAMP' => $timestamp,
            'USERNAME' => $internalUsername,
            'PASSWORD_CLAIM_SECRET_BLOCK' => $secretBlock,
            'PASSWORD_CLAIM_SIGNATURE' => base64_encode(hash_hmac('sha256', $message, $hkdf, true)),
        ];
    }

    /**
     * Timestamp in the exact format Cognito expects, e.g. "Tue Sep 3 05:04:09 UTC 2024" (no day padding).
     */
    public static function timestamp(DateTimeInterface $now): string
    {
        $utc = \DateTimeImmutable::createFromInterface($now)->setTimezone(new DateTimeZone('UTC'));

        return $utc->format('D M j H:i:s \U\T\C Y');
    }

    private function passwordAuthenticationKey(string $username, string $password, BigInteger $serverB, string $saltHex): string
    {
        [, $bModN] = $serverB->divide($this->bigN);

        if ($bModN->equals(new BigInteger(0))) {
            throw new AuthenticationException('SRP safety check for B failed.');
        }

        $u = new BigInteger($this->hexHash($this->padHex($this->largeA).$this->padHex($serverB)), 16);

        if ($u->equals(new BigInteger(0))) {
            throw new AuthenticationException('SRP value U cannot be zero.');
        }

        $usernamePasswordHash = $this->hashSha256($this->poolName.$username.':'.$password);
        $x = new BigInteger($this->hexHash($this->padHex($saltHex).$usernamePasswordHash), 16);

        // S = (B - k * g^x) ^ (a + u * x) mod N, keeping the base positive.
        [, $kgx] = $this->k->multiply($this->g->modPow($x, $this->bigN))->divide($this->bigN);
        $base = $serverB->subtract($kgx);
        [, $base] = $base->divide($this->bigN);

        if ($base->isNegative()) {
            $base = $base->add($this->bigN);
        }

        $s = $base->modPow($this->smallA->add($u->multiply($x)), $this->bigN);

        return $this->computeHkdf((string) hex2bin($this->padHex($s)), (string) hex2bin($this->padHex($this->longToHex($u))));
    }

    private function computeHkdf(string $ikm, string $salt): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        return substr(hash_hmac('sha256', self::INFO_BITS.chr(1), $prk, true), 0, 16);
    }

    private function hashSha256(string $buffer): string
    {
        return str_pad(hash('sha256', $buffer), 64, '0', STR_PAD_LEFT);
    }

    private function hexHash(string $hex): string
    {
        return $this->hashSha256((string) hex2bin($hex));
    }

    private function longToHex(BigInteger $value): string
    {
        $hex = ltrim($value->toHex(), '0');

        return $hex === '' ? '0' : $hex;
    }

    /**
     * Hex representation padded for hashing: even length, and a leading 00 when the high bit is set.
     */
    private function padHex(BigInteger|string $value): string
    {
        $hex = $value instanceof BigInteger ? $this->longToHex($value) : $value;

        if (strlen($hex) % 2 === 1) {
            return '0'.$hex;
        }

        return str_contains('89ABCDEFabcdef', $hex[0]) ? '00'.$hex : $hex;
    }

    /** @param array<array-key, mixed> $parameters */
    private function param(array $parameters, string $key): string
    {
        $value = $parameters[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new AuthenticationException("Cognito SRP challenge is missing '{$key}'.");
        }

        return $value;
    }
}
