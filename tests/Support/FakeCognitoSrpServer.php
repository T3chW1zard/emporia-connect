<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Support;

use phpseclib3\Math\BigInteger;

/**
 * Server side of the Cognito SRP handshake, used to prove the client computes a password
 * claim that a real verifier (knowing only salt + verifier) accepts.
 */
final readonly class FakeCognitoSrpServer
{
    private const N_HEX = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E208E24FA074E5AB3143DB5BFCE0FD108E4B82D120A93AD2CAFFFFFFFFFFFFFFFF';

    public string $salt;

    public string $secretBlock;

    private BigInteger $n;

    private BigInteger $g;

    private BigInteger $verifier;

    private BigInteger $b;

    private BigInteger $largeB;

    public function __construct(
        private string $poolName,
        public string $userId,
        string $password,
    ) {
        $this->n = new BigInteger(self::N_HEX, 16);
        $this->g = new BigInteger(2);
        $this->salt = 'f'.bin2hex(random_bytes(15));
        $this->secretBlock = base64_encode(random_bytes(32));

        $x = $this->hexToInt($this->hexHash($this->pad($this->salt).$this->hash($poolName.$userId.':'.$password)));
        $this->verifier = $this->g->modPow($x, $this->n);

        $k = $this->hexToInt($this->hexHash('00'.self::N_HEX.'02'));
        $this->b = new BigInteger(bin2hex(random_bytes(64)), 16);
        [, $this->largeB] = $k->multiply($this->verifier)->add($this->g->modPow($this->b, $this->n))->divide($this->n);
    }

    /** @return array<string, string> */
    public function challengeParameters(): array
    {
        return [
            'USER_ID_FOR_SRP' => $this->userId,
            'USERNAME' => $this->userId,
            'SALT' => $this->salt,
            'SRP_B' => $this->largeB->toHex(),
            'SECRET_BLOCK' => $this->secretBlock,
        ];
    }

    /**
     * @param  array<string, string>  $responses  ChallengeResponses sent by the client
     */
    public function verify(string $srpA, array $responses): bool
    {
        $largeA = new BigInteger($srpA, 16);
        $u = $this->hexToInt($this->hexHash($this->pad($this->hex($largeA)).$this->pad($this->hex($this->largeB))));

        // S = (A * v^u) ^ b mod N
        [, $base] = $largeA->multiply($this->verifier->modPow($u, $this->n))->divide($this->n);
        $s = $base->modPow($this->b, $this->n);

        $prk = hash_hmac('sha256', (string) hex2bin($this->pad($this->hex($s))), (string) hex2bin($this->pad($this->hex($u))), true);
        $key = substr(hash_hmac('sha256', 'Caldera Derived Key'.chr(1), $prk, true), 0, 16);

        $message = $this->poolName.$this->userId.base64_decode($this->secretBlock).$responses['TIMESTAMP'];
        $expected = base64_encode(hash_hmac('sha256', $message, $key, true));

        return hash_equals($expected, $responses['PASSWORD_CLAIM_SIGNATURE'])
            && $responses['PASSWORD_CLAIM_SECRET_BLOCK'] === $this->secretBlock
            && $responses['USERNAME'] === $this->userId;
    }

    private function hexToInt(string $hex): BigInteger
    {
        return new BigInteger($hex, 16);
    }

    private function hex(BigInteger $value): string
    {
        return ltrim($value->toHex(), '0') ?: '0';
    }

    private function pad(string $hex): string
    {
        if (strlen($hex) % 2 === 1) {
            return '0'.$hex;
        }

        return str_contains('89abcdefABCDEF', $hex[0]) ? '00'.$hex : $hex;
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    private function hexHash(string $hex): string
    {
        return hash('sha256', (string) hex2bin($hex));
    }
}
