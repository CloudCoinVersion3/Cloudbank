<?php

/**
 * Balance Controller
 * Handles balance-related business logic and data processing
 */

class BalanceController {
    
    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Get balance data for the view
     * @return array Contains balance and error status
     */
    public function getBalanceData() {
        $balance = $this->fetchBalance();
        $isError = ($balance === "Error fetching balance");
        
        return [
            'balance' => $balance,
            'isError' => $isError
        ];
    }
    
    /**
     * Fetch balance from API
     * @return string|float Balance amount or error message
     */
    private function fetchBalance() {
        if (!isset($_SESSION['phone_number'])) {
            return "Error fetching balance";
        }
        
        $phoneNumber = $_SESSION['phone_number'];
        
        // Call the API function (make sure this is available)
        if (function_exists('getBalance')) {
            $balance = getBalance($phoneNumber);
            
            if ($balance === false) {
                return "Error fetching balance";
            }
            
            return $balance;
        } else {
            // If getBalance function is not available, return a placeholder
            return "Error: API function not available";
        }
    }
}
