<?php
/**
 * Transactions Model
 * Handles fetching and processing of transaction data.
 */

require_once __DIR__ . '/../models/WalletService.php';

class Transactions
{
    private $walletService;

    public function __construct()
    {
        $this->walletService = new WalletService();
    }

    /**
     * Gets and processes transactions for a given phone number.
     *
     * @param string $phoneNumber The user's phone number.
     * @return array|null An array of processed transactions or null on failure.
     */
    public function getProcessedTransactions($phoneNumber)
    {
        // Get current wallet info to find the latest balance
        $wallet = $this->walletService->getWallet($phoneNumber);
        if (!$wallet) {
            error_log("Failed to get wallet information for $phoneNumber");
            return null;
        }

        // Fetch raw transactions from the service
        $transactions = $this->walletService->getTransactions($phoneNumber);
        if ($transactions === null) {
            return null;
        }

        // Process transactions to calculate a running balance for each
        return $this->processRunningBalance($transactions, (float)$wallet->balance);
    }

    /**
     * Calculates the running balance for a set of transactions.
     *
     * @param array $transactions The list of transactions.
     * @param float $startingBalance The most recent balance to start calculations from.
     * @return array The transactions with the running_balance property added.
     */
    private function processRunningBalance(array $transactions, float $startingBalance)
{
    $processedTransactions = [];
    $runningBalance = $startingBalance;

    // Transactions are in chronological order. Reverse them to calculate
    // the running balance from the most recent transaction backwards.
    $reversedTransactions = array_reverse($transactions);

    foreach ($reversedTransactions as $transaction) {
        $amount = (float)($transaction->amount ?? 0);
        
        // The running_balance for this transaction is the balance *before* this transaction occurred.
        $transaction->running_balance = $runningBalance;

        // Now, adjust the running balance for the *next oldest* transaction.
        // We do the opposite of the transaction type to go back in time.
        switch ($transaction->type) {
            // DEBITS: These are outgoing funds. To go back in time, we ADD the amount back.
            case 'PutToLocker':
            case 'PutToExchangeLocker':
            case 'Export':
            case 'TransferOut':
            case 'Transfer.Out':
                $runningBalance += $amount;
                break;

            // CREDITS: These are incoming funds. To go back in time, we SUBTRACT the amount.
            case 'GetFromLocker':
            case 'WithdrawFromExchangeLocker':
            case 'Import':
            case 'TransferIn':
            case 'Transfer.In':
                $runningBalance -= $amount;
                break;

            // SPECIAL: Adjustments can be positive or negative.
            case 'Adjustment':
                if (isset($transaction->negative) && $transaction->negative) {
                    $runningBalance += $amount; // It was a negative adjustment, so add back.
                } else {
                    $runningBalance -= $amount; // It was a positive adjustment, so subtract.
                }
                break;
        }

        $processedTransactions[] = $transaction;
    }

    return $processedTransactions;
}
}