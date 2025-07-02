<?php
/**
 * Wallet Service Class
 * Handles all wallet-related operations
 */

require_once __DIR__ . '/CloudBankAPI.php';

class WalletService {
    
    private $api;
    
    /**
     * Constructor for WalletService.
     * Initializes a new CloudBankAPI instance.
     */
    public function __construct() {
        $this->api = new CloudBankAPI();
    }
    
    /**
     * Retrieves a wallet by its name (phone number).
     * If the wallet does not exist, it automatically creates one.
     *
     * @param string $name The wallet name (phone number).
     * @return stdClass|false The wallet object on success, false on failure.
     */
    public function getWallet($name) {
        $name = CloudBankAPI::normalizePhoneNumber($name);
        $data = $this->api->callMethod("wallets/$name", "GET");
        if (!$data) return false;
        
        if (isset($data->status) && $data->status == "error" && isset($data->payload->code) && $data->payload->code == 4115) {
            $this->api->logData("No such wallet. Creating one for $name");
            $rv = $this->api->callMethod("wallets", "POST", ["name" => $name]);
            if ($this->api->isApiError($rv)) return false;
            $data = $this->api->callMethod("wallets/$name", "GET");
        }
        
        if ($this->api->isApiError($data)) return false;
        
        return $data->payload;
    }
    
    /**
     * Gets the balance for a specific wallet.
     *
     * @param string $name The wallet name (phone number).
     * @return float|false The wallet balance on success, false on failure.
     */
    public function getBalance($name) {
        $wallet = $this->getWallet($name);
        return $wallet ? $wallet->balance : false;
    }
    
    /**
     * Deposits CloudCoins into a wallet using a deposit code.
     * Streams the progress of the deposit operation to the client.
     *
     * @param string $name The wallet name (phone number).
     * @param string $code The deposit code.
     * @param string &$error A reference to a variable to store error messages.
     * @return bool True on success, false on failure.
     */
    public function deposit($name, $code, &$error) {
        // This method correctly uses the streaming monitorAsyncTask
        $code = strtoupper(trim($code));
        $code = CloudBankAPI::insertHyphen($code);
        $name = CloudBankAPI::normalizePhoneNumber($name);
        $wallet = $this->getWallet($name);
        if (!$wallet) { $error = "Wallet not found"; return false; }
        
        $data = ["name" => $name];
        $response = $this->api->callMethod("locker/$code", "POST", $data, true);
        if (!$response || !isset($response->payload->id)) { $error = "Failed to start deposit"; return false; }
        
        $result = $this->api->monitorAsyncTask($response->payload->id, $error);
        if ($result === null) return false;
        
        echo json_encode(['status' => 'completed', 'data' => $result]) . "\n";
        return true;
    }
    
    /**
     * Withdraws a specified amount from a wallet.
     * Streams the progress of the withdrawal operation to the client.
     *
     * @param string $name The wallet name (phone number).
     * @param float $amount The amount to withdraw.
     * @param string &$error A reference to a variable to store error messages.
     * @return bool True on success, false on failure.
     */
    public function withdraw($name, $amount, &$error) {
        // This method correctly uses the streaming monitorAsyncTask
        $name = CloudBankAPI::normalizePhoneNumber($name);
        $amount = round((float)$amount, 2);
        if ($amount <= 0) { $error = "Invalid amount"; return false; }
        
        $wallet = $this->getWallet($name);
        if (!$wallet) { $error = "Wallet not found"; return false; }
        if ($amount > $wallet->balance) { $error = "Insufficient balance"; return false; }

        $data = ["name" => $name, "amount" => $amount];
        $result = $this->api->callAsyncMethod("locker", $error, "POST", $data);
        if ($result === null) return false;
        
        $finalResponse = ['status' => 'completed', 'data' => ['amount' => $amount, 'transmit_code' => $result->transmit_code]];
        echo json_encode($finalResponse) . "\n";
        return true;
    }
    
    /**
     * Sends a specified amount of CloudCoins from one wallet to another.
     *
     * @param string $name The phone number of the sender's wallet.
     * @param int $amount The integer amount of coins to send.
     * @param string $to The phone number of the recipient's wallet.
     * @param string $tag A short message or memo for the transaction.
     * @param string &$error A reference to a variable to store error messages.
     * @return bool Returns true on success, false on failure.
     */
    public function send($name, $amount, $to, $tag, &$error) {
        $this->api->logData("Sending $amount from sender '$name' to recipient '$to'");
        
        $name = CloudBankAPI::normalizePhoneNumber($name);
        $amount = intval(trim($amount));
        
        if ($amount <= 0) { $error = "Invalid amount"; return false; }
        if (!preg_match('/^\+?\d{5,}$/', $to)) { $error = "Invalid recipient phone number"; return false; }
        
        $wallet = $this->getWallet($name);
        if (!$wallet) { $error = "Failed to get sender's wallet"; return false; }
        
        $to_normalized = CloudBankAPI::normalizePhoneNumber($to);
        $rwallet = $this->getWallet($to_normalized);
        if (!$rwallet) { $error = "Failed to get recipient's wallet"; return false; }
        
        if ($amount > $wallet->balance) { $error = "Not enough coins. Your balance is {$wallet->balance}."; return false; }
        
        $data = ["srcname" => $name, "amount" => $amount, "dstname" => $to_normalized, "tag" => trim($tag)];
        

        $result = $this->api->callAsyncMethodAndWait("transfer", $error, 'POST', $data);
        
        if ($result === null) {
            $this->api->logError("Failed to send: " . ($error ?: 'Unknown API error during transfer.'));
            return false;
        }
        
        $this->api->logData("Transfer successful");
        return true;
    }


    /**
     * Fetches the list of transactions for a given wallet.
     *
     * @param string $name The wallet name (phone number).
     * @return array|null The list of transactions or null on failure.
     */
    public function getTransactions($name) {
        $name = CloudBankAPI::normalizePhoneNumber($name);
        $data = $this->api->callMethod("wallets/$name", "GET");

        if ($this->api->isApiError($data)) {
            $this->api->logError("Failed to fetch transactions for wallet: $name");
            return null;
        }

        return $data->payload->transactions ?? [];
    }
}
?>
