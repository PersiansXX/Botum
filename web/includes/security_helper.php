<?php
/**
 * Security Helper - Functions for securely storing and retrieving API credentials
 */

// Encryption key - You should store this in a secure environment variable in production
define('ENCRYPTION_KEY', hash('sha256', 'TradingBotSecretKey'));

/**
 * Encrypts sensitive data before storing in database
 * 
 * @param string $data The data to encrypt
 * @return string The encrypted data
 */
function encrypt_data($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypts stored data from database
 * 
 * @param string $encryptedData The encrypted data to decrypt
 * @return string The decrypted data
 */
function decrypt_data($encryptedData) {
    list($encrypted_data, $iv) = explode('::', base64_decode($encryptedData), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
}

/**
 * Saves API settings to database
 * 
 * @param int $user_id The user ID
 * @param array $api_settings Array containing API keys and settings
 * @return bool True on success, false on failure
 */
function save_api_settings($user_id, $api_settings) {
    global $db; // Assuming database connection is available
    
    // Sanitize user ID
    $user_id = (int)$user_id;
    
    // Encrypt sensitive data
    $encrypted_api_key = encrypt_data($api_settings['api_key']);
    $encrypted_api_secret = encrypt_data($api_settings['api_secret']);
    
    // Check if settings already exist for this user
    $stmt = $db->prepare("SELECT id FROM api_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing settings
        $stmt = $db->prepare("UPDATE api_settings SET 
                            api_key = ?, 
                            api_secret = ?,
                            exchange = ?,
                            updated_at = NOW() 
                            WHERE user_id = ?");
        $stmt->bind_param("sssi", 
                       $encrypted_api_key,
                       $encrypted_api_secret,
                       $api_settings['exchange'],
                       $user_id);
    } else {
        // Insert new settings
        $stmt = $db->prepare("INSERT INTO api_settings 
                           (user_id, api_key, api_secret, exchange, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("isss", 
                       $user_id,
                       $encrypted_api_key, 
                       $encrypted_api_secret,
                       $api_settings['exchange']);
    }
    
    return $stmt->execute();
}

/**
 * Retrieves API settings from database
 * 
 * @param int $user_id The user ID
 * @param bool $show_masked Whether to show masked credentials (default true)
 * @return array|false API settings array or false if not found
 */
function get_api_settings($user_id, $show_masked = true) {
    global $db; // Assuming database connection is available
    
    // Sanitize user ID
    $user_id = (int)$user_id;
    
    $stmt = $db->prepare("SELECT * FROM api_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    
    if ($show_masked) {
        // Return masked data for display
        return [
            'api_key' => '********',
            'api_secret' => '********',
            'exchange' => $row['exchange'],
            'has_credentials' => true
        ];
    } else {
        // Return decrypted data (for internal operations only)
        return [
            'api_key' => decrypt_data($row['api_key']),
            'api_secret' => decrypt_data($row['api_secret']),
            'exchange' => $row['exchange']
        ];
    }
}

/**
 * Validates if API settings exist for a user
 * 
 * @param int $user_id The user ID
 * @return bool True if settings exist, false otherwise
 */
function has_api_settings($user_id) {
    global $db;
    
    $user_id = (int)$user_id;
    
    $stmt = $db->prepare("SELECT id FROM api_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result->num_rows > 0);
}
?>
