<?php
namespace App\Service;

use DateTimeImmutable;

class JWTService
{
    // We generate the token

    /**
     * Génération du JWT
     * @param array $header 
     * @param array $payload 
     * @param string $secret 
     * @param int $validity 
     * @return string 
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 10800): string
    {
        if($validity > 0){
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;
    
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

        // We encode in base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        // We "clean" the encoded values ​​(removal of the +, / and =)
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        // We generate the signature
        $secret = base64_encode($secret);

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);

        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // We create the token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        return $jwt;
    }

    // We check that the token is valid (correctly formed)

    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    // We retrieve the Payload (to check the expiration of the token)
    public function getPayload(string $token): array
    {
        // We dismantle the token
        $array = explode('.', $token);

        // We decode the Payload
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    // We retrieve the Header (to verify the signature of the token)
    public function getHeader(string $token): array
    {
        // We dismantle the token
        $array = explode('.', $token);

        // We decode the Header
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    // We check if the token has expired
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);

        $now = new DateTimeImmutable();

        return $payload['exp'] < $now->getTimestamp();
    }

    // We verify the signature of the Token
    public function check(string $token, string $secret)
    {
        // We get the header and the payload
        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        // We regenerate a token
        $verifToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifToken;
    }
}