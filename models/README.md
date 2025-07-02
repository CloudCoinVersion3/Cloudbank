Models
This directory contains the data models and services for the CloudBank application. These classes are responsible for all interactions with the backend CloudBank API.

Files
CloudBankAPI.php: The core class for communicating with the CloudBank API. It handles all HTTP requests, responses, and error logging.

WalletService.php: Provides a high-level interface for wallet-related operations, such as getting a wallet, checking the balance, and managing transactions. It uses the CloudBankAPI class to perform these actions.

ServiceFactory.php: A factory class that provides a single point of access to the various services in the application. This helps to manage dependencies and ensures that services are instantiated only when needed.