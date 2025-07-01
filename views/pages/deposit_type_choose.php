<?php
// File: views/pages/deposit_type_choose.php

require_once 'utils/csrf_functions.php';
?>

<style>
    .deposit-options {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 30px;
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }
    
    .deposit-option-btn {
        width: 100%;
        padding: 15px 30px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 500;
        color: #4a90e2;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .deposit-option-btn:hover {
        background: rgba(255, 255, 255, 1);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="form-container">
    <h1 id="main-heading">Deposit CloudCoins</h1>

    <div class="deposit-options">
        <button class="deposit-option-btn" onclick="window.location.href='index.php?page=deposit&type=code'">
            Deposit by Code
        </button>
        <button class="deposit-option-btn" onclick="window.location.href='index.php?page=deposit&type=file'">
            Deposit by File
        </button>
    </div>
</div>
