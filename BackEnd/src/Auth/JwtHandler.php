<?php
namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtHandler {
    private static $secret;
    private static $alg = 'HS256';

    // Inicializador
    private static function init() {
        if (!self::$secret) {
            self::$secret = $_ENV['JWT_SECRET'];
        }
    }

    public static function encode(array $data, $exp = '+4 hours') {
        self::init(); // Asegura que la secret esté cargada

        $payload = [
            'iss' => 'ard_api',
            'iat' => time(),
            'exp' => strtotime($exp),
            'data' => $data
        ];
        return JWT::encode($payload, self::$secret, self::$alg);
    }

    public static function decode($token) {
        self::init(); // Asegura que la secret esté cargada
        try {
            return JWT::decode($token, new Key(self::$secret, self::$alg));
        } catch (Exception $e) {
            return null;
        }
    }
}
