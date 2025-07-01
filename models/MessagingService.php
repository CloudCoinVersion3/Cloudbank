<?php
/**
 * Messaging Service Class
 * 
 * Handles all SMS and messaging operations using Twilio
 */

// Load Twilio if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Twilio\TwiML\MessagingResponse;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class MessagingService {
    
    private $accountSid;
    private $authToken;
    private $twilioPhone;
    private $logDir;
    
    public function __construct() {
        // Get Twilio config
        $this->accountSid = get_twilio_config('account_sid');
        $this->authToken = get_twilio_config('auth_token');
        $this->twilioPhone = get_twilio_config('twilio_phone');
        
        $this->logDir = __DIR__ . "/../logs";
        
        // Ensure log directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    /**
     * Log data to SMS log file
     */
    public function logData($msg) {
        $d = date("Y-m-d H:i:s");
        $logFile = $this->logDir . "/sms.log";
        file_put_contents($logFile, "$d: $msg\n", FILE_APPEND);
    }
    
    /**
     * Log error message
     */
    public function logError($msg) {
        $this->logData("Error: $msg");
    }
    
    /**
     * Send SMS message to a phone number
     */
    public function sendPeerMessage($to, $body) {
        $this->logData("Sending to $to");
        
        if (!class_exists('Twilio\Rest\Client')) {
            $this->logError("Twilio library not found. Please install it using Composer.");
            throw new Exception("Twilio library not found. Please install it using Composer.");
        }
        
        try {
            $client = new Client($this->accountSid, $this->authToken);
            $message = $client->messages->create("+" . $to, [
                "from" => $this->twilioPhone, 
                "body" => $body
            ]);
            $this->logData("Sent");
            return true;
        } catch (TwilioException $e) {
            $this->logError("Twilio error: " . $e->getMessage());
            throw new Exception("Failed to send message: " . $e->getMessage());
        } catch (Exception $e) {
            $this->logError("Error: " . $e->getMessage());
            throw new Exception("An unexpected error occurred: " . $e->getMessage());
        }
    }
    
    /**
     * Send TwiML response
     */
    public function sendMessage($message) {
        header("Content-Type: text/xml");
        $response = new MessagingResponse();
        $response->message($message);
        echo $response;
    }
    
    /**
     * Write message to file system
     */
    public function writeMessageToFile($path, $message, $phone) {
        // Create the full path for the "Messages" subfolder
        $messagesFolderPath = rtrim($path, '/');
        
        // Check if the Messages folder exists, if not create it
        if (!is_dir($messagesFolderPath)) {
            if (!mkdir($messagesFolderPath, 0777, true)) {
                throw new Exception("Failed to create " . $messagesFolderPath);
            }
        }
        
        // Generate the filename using the first 10 characters of the message
        $filename = substr($message, 0, 10);
        
        // Remove any characters that are not allowed in filenames
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        
        // Create the full file path
        $filePath = $messagesFolderPath . '/' . $filename . '.txt';
        
        // Write the message to the file
        if (file_put_contents($filePath, $message) === false) {
            throw new Exception("Failed to write the message to the file.");
        }
        
        return true;
    }
    
    /**
     * Read and delete all messages from directory
     */
    public function readAndDeleteMessages($path) {
        // Create the full path for the "Messages" subfolder
        $messagesFolderPath = rtrim($path, '/');
        
        // Check if the Messages folder exists
        if (!is_dir($messagesFolderPath)) {
            return "No messages found";
        }
        
        // Get all .txt files in the Messages folder
        $files = glob($messagesFolderPath . '/*.txt');
        
        if (empty($files)) {
            return "No messages found.";
        }
        
        // Sort files by modification time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $allMessages = "";
        
        // Read and concatenate file contents, then delete each file
        foreach ($files as $file) {
            // Read file contents
            $content = file_get_contents($file);
            if ($content === false) {
                return "Failed to read file: " . basename($file);
            }
            
            // Append content to allMessages
            $allMessages .= $content . "\n\n";
            
            // Delete the file
            if (!unlink($file)) {
                return trim($allMessages) . "Failed to delete message: " . basename($file);
            }
        }
        
        return trim($allMessages);
    }
}