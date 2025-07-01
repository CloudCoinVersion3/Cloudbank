<?php
// File: models/send.php

require_once __DIR__ . '/WalletService.php';

class SendModel {
    private $walletService;

    public function __construct() {
        $this->walletService = new WalletService();
    }

    /**
     * Processes the sending of coins from one user to another.
     *
     * @param string $senderPhone The phone number of the person sending coins.
     * @param int $amount The amount of coins to send.
     * @param string $recipientPhone The phone number of the recipient.
     * @param string $tag A message or tag for the transaction.
     * @return array An associative array with 'success' status and a 'message'.
     */
    public function sendCoins($senderPhone, $amount, $recipientPhone, $tag) {
        // Normalize both phone numbers for a reliable comparison
        $normalizedSender = CloudBankAPI::normalizePhoneNumber($senderPhone);
        $normalizedRecipient = CloudBankAPI::normalizePhoneNumber($recipientPhone);

        // Check if the sender and recipient are the same.
        if ($normalizedSender === $normalizedRecipient) {
            return [
                'success' => true,
                'message' => 'Self-transfer successful. The amount has been returned to your wallet.'
            ];
        }

        $error = '';
        
        // This now calls the corrected WalletService->send method.
        $success = $this->walletService->send($senderPhone, $amount, $recipientPhone, $tag, $error);

        if ($success) {
            return [
                'success' => true,
                'message' => 'Successfully sent ' . $amount . ' CloudCoins.'
            ];
        } else {
            return [
                'success' => false,
                'message' => $error ?: 'An unknown error occurred while sending.'
            ];
        }
    }
}
