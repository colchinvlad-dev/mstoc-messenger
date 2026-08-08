<?php

class Encrypt {
    private static $cipher = 'aes-256-gcm';
    private static $key = 'MTSoc2026SecureKey32BytesLong!!';

    /**
     * Шифрование данных
     */
    public static function encrypt($data) {
        if (empty($data)) return '';
        
        $iv = random_bytes(16);
        $tag = '';
        $encrypted = openssl_encrypt(
            $data,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Дешифрование данных
     */
    public static function decrypt($encrypted) {
        if (empty($encrypted)) return '';
        
        $data = base64_decode($encrypted);
        if ($data === false || strlen($data) < 32) return '';
        
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $ciphertext = substr($data, 32);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Хеширование пароля
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Проверка пароля
     */
    public static function verifyPassword($password, $hash) {
        if (empty($password) || empty($hash)) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * Генерация токена
     */
    public static function generateToken($length = 64) {
        return bin2hex(random_bytes($length));
    }
}
?>