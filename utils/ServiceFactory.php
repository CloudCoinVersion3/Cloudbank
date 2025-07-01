<?php
/**
 * Service Factory
 * 
 * Provides easy access to all services throughout the application
 */

require_once __DIR__ . '/../models/CloudBankAPI.php';
require_once __DIR__ . '/../models/WalletService.php';
require_once __DIR__ . '/../models/MessagingService.php';

class ServiceFactory {
    
    private static $instances = [];
    
    /**
     * Get WalletService instance
     */
    public static function getWalletService() {
        if (!isset(self::$instances['wallet'])) {
            self::$instances['wallet'] = new WalletService();
        }
        return self::$instances['wallet'];
    }
    
    /**
     * Get MessagingService instance
     */
    public static function getMessagingService() {
        if (!isset(self::$instances['messaging'])) {
            self::$instances['messaging'] = new MessagingService();
        }
        return self::$instances['messaging'];
    }
    
    /**
     * Get CloudBankAPI instance
     */
    public static function getAPI() {
        if (!isset(self::$instances['api'])) {
            self::$instances['api'] = new CloudBankAPI();
        }
        return self::$instances['api'];
    }
}

// Backward compatibility functions for existing code
function getBalance($name) {
    return ServiceFactory::getWalletService()->getBalance($name);
}

function getWallet($name) {
    return ServiceFactory::getWalletService()->getWallet($name);
}

function deposit($name, $code, &$error) {
    return ServiceFactory::getWalletService()->deposit($name, $code, $error);
}

function withdraw($name, $amount, &$error) {
    return ServiceFactory::getWalletService()->withdraw($name, $amount, $error);
}

function send($name, $amount, $to, $tag, &$error) {
    return ServiceFactory::getWalletService()->send($name, $amount, $to, $tag, $error);
}

function sendPeerMessage($to, $body) {
    return ServiceFactory::getMessagingService()->sendPeerMessage($to, $body);
}

function sendMessage($message) {
    return ServiceFactory::getMessagingService()->sendMessage($message);
}

function writeMessageToFile($path, $message, $phone) {
    return ServiceFactory::getMessagingService()->writeMessageToFile($path, $message, $phone);
}

function readAndDeleteMessages($path) {
    return ServiceFactory::getMessagingService()->readAndDeleteMessages($path);
}

function logData($msg) {
    return ServiceFactory::getAPI()->logData($msg);
}

function logError($msg) {
    return ServiceFactory::getAPI()->logError($msg);
}

function normalizeFrom($from) {
    return CloudBankAPI::normalizePhoneNumber($from);
}

function insertHyphen($string) {
    return CloudBankAPI::insertHyphen($string);
}