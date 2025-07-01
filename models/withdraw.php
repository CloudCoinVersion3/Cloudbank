<?php
// File: models/withdraw.php

require_once __DIR__ . '/WalletService.php';

class WithdrawModel {
    private $walletService;

    public function __construct() {
        $this->walletService = new WalletService();
    }

    /**
     * Processes a withdrawal request by delegating to the WalletService.
     * This function will not return a value; it lets the WalletService
     * stream its progress directly to the output.
     *
     * @param string $phoneNumber The user's phone number.
     * @param float  $amount The amount to withdraw.
     * @throws Exception if the underlying service throws an exception.
     */
    public function processWithdrawal($phoneNumber, $amount) {
        $error = ''; // WalletService expects an error variable by reference.
        
        // This call will now handle everything, including echoing the
        // streaming JSON progress and the final result.
        $this->walletService->withdraw($phoneNumber, $amount, $error);

        // If the service failed and set an error message before it could stream
        // its own error, throw an exception so the controller can catch it.
        if (!empty($error)) {
            throw new Exception($error);
        }
    }
}
