<?php
// This file is loaded by statement_controller.php, so session is already started.
require_once __DIR__ . '/../../utils/csrf_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../partials/common_head_content.php' ?>
    <title>Statement - CloudBank</title>
    <style>
        /* All your original CSS goes here. It is unchanged. */
        .main-container{
            max-width: 1400px;
        }
        #main-heading {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 25px;
            margin-bottom: 0;
            background: rgba(255, 255, 255, 0.4);
        }
        .back-button {
            background: transparent;
            color: #4a90e2;
            cursor: pointer;
            font-size: 25px;
            font-weight: bold;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid #4a90e2;
            width: 100%;
            border-radius: 5px;
            padding: 0.5rem
        }
        .back-button:hover {
            transform: translateX(-3px);
        }
        .statement-title {
            margin: 0;
            text-align: left;
        }
        .transactions-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px 0;
            position: relative;
        }
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; 
        }
        .table-container::-webkit-scrollbar {
            height: 8px;
        }
        .table-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: rgba(74, 144, 226, 0.5);
            border-radius: 4px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(74, 144, 226, 0.7);
        }
        .transactions-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .transactions-table th {
            background: #1D5894;
            color: white;
            font-weight: 500;
            text-align: left;
            padding: 16px 25px;
            font-size: 14px;
        }
        .transactions-table td {
            padding: 16px 25px;
            color: #2c3e50;
            font-size: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            white-space: nowrap;
            min-width: 120px;
        }
        .transactions-table td:first-child {
            min-width: 160px; 
        }
        .transactions-table td:nth-child(4) {
            min-width: 200px; 
            white-space: normal; 
        }
        .transactions-table tr:hover {
            background: rgba(255, 255, 255, 0.4);
        }
        .amount-positive {
            color: #155724 !important;
            font-weight: 500;
        }
        .amount-negative {
            color: #721c24 !important;
            font-weight: 500;
        }
        .transaction-type {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .type-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            margin-right: 8px;
            flex-shrink: 0;
        }
        .type-icon.adjustment {
            background:rgba(220, 53, 69, 0.1);
            color: #721c24;
            border: 1px solid #dc3545;
        }
        .type-icon.deposit {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border: 1px solid #28a745;
        }
        .type-icon.withdrawal {
            background:rgba(220, 53, 69, 0.1);
            color: #721c24;
            border: 1px solid #dc3545;
        }
        .type-icon.transfer {
            background: rgba(74, 144, 226, 0.1); 
            color: #0056b3; 
            border: 1px solid #4a90e2;
        }

        .type-icon.adjustment {
            background: rgba(255, 193, 7, 0.1);
            color: #856404; 
            border: 1px solid #ffc107; 
        }

        .type-icon.exchange {
            background: rgba(108, 92, 231, 0.1); 
            color: #4834d4; 
            border: 1px solid #6c5ce7; 
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.2);
        }
        .page-navigation {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .page-button {
            background-color: white;
            border: none;
            color: #4a90e2;
            border: 2px solid #4a90e2;
            padding: 5px 15px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
        }
        .page-button:hover {
            background: #f8f9fa;
        }
        .page-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .pagination-info {
            color: #2c3e50;
            font-size: 14px;
            margin: 0 15px;
            white-space: nowrap;
        }
        .loader {
            display: flex;
            justify-content: center;
            padding: 40px;
        }
        .loader-spin {
            border: 3px solid rgba(74, 144, 226, 0.1);
            border-radius: 50%;
            border-top: 3px solid #4a90e2;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            background:rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #721c24;
            padding: 16px 25px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        @media (max-width: 768px) {
            .back-button {
                height: auto;
                padding: 5px;
            }
        }
    </style>
</head>

<body>
    
    
        <div id="loader" class="transactions-table">
            <div id="main-heading">
                <form action="balance_controller.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="back-button">←</button>
                </form>
                <h1 class="statement-title">Statement</h1>
            </div>
            <div class="loader">
                <div class="loader-spin"></div>
            </div>
        </div>

        <div id="transactionsContent" class="transactions-table" style="display: none;">
            <div id="main-heading">
                 <form action="balance_controller.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="back-button">←</button>
                </form>
                <h1 class="statement-title">Statement</h1>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>From</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Amount</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsBody">
                        </tbody>
                </table>
            </div>
            <div class="pagination">
                <div class="page-navigation">
                    <button onclick="changePage(-1)" class="page-button">Previous</button>
                    <span class="pagination-info" id="paginationInfo"></span>
                    <button onclick="changePage(1)" class="page-button">Next</button>
                </div>
            </div>
        </div>

        <div id="errorContent" class="error-message" style="display: none;">
            Unable to fetch the statement. Please try again later.
        </div>

    <script>
    let currentPage = 1;
    const RECORDS_PER_PAGE = 10;
    let allTransactions = [];

    
    function formatDate(dateString) {
        if (!dateString) {
            console.error('No datetime provided:', dateString);
            return '-';
        }
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                console.error('Invalid date:', dateString);
                return '-';
            }
            
            const day = date.getDate().toString().padStart(2, '0');
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            
            let hours = date.getHours();
            const minutes = date.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'pm' : 'am';
            
            hours = hours % 12;
            hours = hours ? hours : 12;
            
            return `${day}/${month}/${year}, ${hours}:${minutes} ${ampm}`;
        } catch (e) {
            console.error('Date parsing error:', e, 'for date string:', dateString);
            return '-';
        }
    }

    function formatAmount(amount) {
        if (!amount || isNaN(amount)) return '0.0';
        return parseFloat(amount).toFixed(1);
    }

  

    function getTypeIcon(type) {
    let icon = '•'; // Default icon
    let className = '';

    switch (type) {
        // --- Credits / Incoming ---
        case 'Import':
            icon = '↓'; 
            className = 'deposit';
            break;
        case 'GetFromLocker':
            icon = '📥'; 
            className = 'deposit';
            break;
        case 'WithdrawFromExchangeLocker':
            icon = '📈'; 
            className = 'exchange';
            break;
        case 'TransferIn':
        case 'Transfer.In': 
            icon = '→'; 
            className = 'transfer';
            break;

        // --- Debits / Outgoing ---
        case 'Export':
            icon = '↑'; 
            className = 'withdrawal';
            break;
        case 'PutToLocker':
            icon = '📤'; 
            className = 'withdrawal';
            break;
        case 'PutToExchangeLocker':
            icon = '📉'; 
            className = 'exchange';
            break;
        case 'TransferOut':
        case 'Transfer.Out': 
            icon = '←'; 
            className = 'transfer';
            break;
        
        case 'Adjustment':
            icon = '⟳';
            className = 'adjustment';
            break;
    }

    return `<div class="type-icon ${className}">${icon}</div>`;
}

    function updatePaginationInfo() {
        const totalRecords = allTransactions.length;
        const startRecord = (currentPage - 1) * RECORDS_PER_PAGE + 1;
        const endRecord = Math.min(currentPage * RECORDS_PER_PAGE, totalRecords);
        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            paginationInfo.textContent = `${startRecord}-${endRecord} of ${totalRecords}`;
        }

        const prevButton = document.querySelector('.page-button:first-child');
        const nextButton = document.querySelector('.page-button:last-child');
        if (prevButton) prevButton.disabled = currentPage === 1;
        if (nextButton) nextButton.disabled = endRecord >= totalRecords;
    }

    function changePage(delta) {
        const maxPage = Math.ceil(allTransactions.length / RECORDS_PER_PAGE);
        const newPage = Math.max(1, Math.min(currentPage + delta, maxPage));
        if (newPage !== currentPage) {
            currentPage = newPage;
            updateTransactionsTable();
        }
    }

    function updateTransactionsTable() {
        const tbody = document.getElementById('transactionsBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        const startIndex = (currentPage - 1) * RECORDS_PER_PAGE;
        const endIndex = startIndex + RECORDS_PER_PAGE;
        const pageTransactions = allTransactions.slice(startIndex, endIndex);

        pageTransactions.forEach(transaction => {
            const row = document.createElement('tr');
            const amount = parseFloat(transaction.amount || 0);
            const balance = parseFloat(transaction.running_balance || 0);
            
            let amountClass, displayAmount;

                const isCredit = [
                    'GetFromLocker', 
                    'Import', 
                    'TransferIn', 
                    'Transfer.In',
                    'WithdrawFromExchangeLocker'
                ];
                const isDebit = [
                    'PutToLocker', 
                    'Export', 
                    'TransferOut', 
                    'Transfer.Out',
                    'PutToExchangeLocker'
                ];

                if (isCredit.includes(transaction.type)) {
                    amountClass = 'amount-positive';
                    displayAmount = `+${Math.abs(amount).toFixed(1)}`;
                } else if (isDebit.includes(transaction.type)) {
                    amountClass = 'amount-negative';
                    displayAmount = `-${Math.abs(amount).toFixed(1)}`;
                } else if (transaction.type === 'Adjustment') {
                    amountClass = transaction.negative ? 'amount-negative' : 'amount-positive';
                    displayAmount = `${transaction.negative ? '-' : '+'}${Math.abs(amount).toFixed(1)}`;
                } else {
                    // Fallback for any other unknown types
                    amountClass = 'unknown-type';
                    displayAmount = amount.toFixed(1);
                }

            row.innerHTML = `
                <td>${formatDate(transaction.datetime)}</td>
                <td>${transaction.from || ''}</td>
                <td>
                    <div class="transaction-type">
                        ${getTypeIcon(transaction.type)}
                        <span class="type-text">${transaction.type}</span>
                    </div>
                </td>
                <td>${transaction.message || ''}</td>
                <td class="${amountClass}">${displayAmount}</td>
                <td>${balance.toFixed(1)}</td>
            `;
            
            tbody.appendChild(row);
        });
        
        updatePaginationInfo();
    }

    function showContent(isError) {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('transactionsContent').style.display = isError ? 'none' : 'block';
        document.getElementById('errorContent').style.display = isError ? 'block' : 'none';
    }


    function refreshTransactions() {
        const xhr = new XMLHttpRequest();
       
        xhr.open('POST', 'controllers/fetch_transactions_controller.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (this.readyState == 4) {
                try {
                    console.log('Raw response:', this.responseText);
                    
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        console.log('Parsed response:', response);

                        if (response.status === 'success' && Array.isArray(response.data)) {
                            allTransactions = response.data;
                            currentPage = 1;
                            updateTransactionsTable();
                            showContent(false);
                        } else {
                            throw new Error(response.error || 'Invalid response format from server.');
                        }
                    } else {
                         // Try to parse the error from JSON response
                        let serverError = 'An unknown server error occurred.';
                        try {
                            const errorResponse = JSON.parse(this.responseText);
                            if(errorResponse.error) {
                                serverError = errorResponse.error;
                            }
                        } catch (e) {
                            // Response was not JSON, use the status text
                            serverError = `Server error: ${this.statusText}`;
                        }
                        throw new Error(serverError);
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    const errorContent = document.getElementById('errorContent');
                    if (errorContent) {
                        errorContent.textContent = e.message;
                    }
                    showContent(true);
                }
            }
        };

        xhr.send('csrf_token=' + encodeURIComponent('<?php echo generateCSRFToken(); ?>'));
    }


    // Initialize on page load
    window.addEventListener('load', refreshTransactions);


</script>
</body>
</html>