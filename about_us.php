<!DOCTYPE html>
<html lang="en">
<head>
   

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About CloudBank</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
</head>

<?php
include './views/partials/header.php'; 
?>

<style>

    .main-container {
        padding-top: 100px;
    }

    .main-content {
        background-color: rgba(255, 255, 255, 0.6);
        border: 1px solid white;
        padding: 2em;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        text-align: center;
        max-width: 800px;
    }

    h1 {
    color: white;
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: center;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}


    p {
        margin-bottom: 1em;
        line-height: 1.6;
        text-align: left
    }

    .warning {
        background-color: rgba(220, 53, 69, 0.1);
        border: 1px solid #dc3545;
        color: #721c24;
        padding: 1em;
        border-radius: 5px;
        margin: 1em 0;
    }

    .link {
        color: #4a90e2;
        text-decoration: none;
    }

    .link:hover {
        text-decoration: underline;
    }

</style>


    <div class="main-container">
        <div class="main-content">
            <h1>About CloudBank</h1>
            
            <p>CloudBank is used to authenticate, store and pay out CC. This software is provided as-is with all faults, defects and errors, and without warranty of any kind. This software is provided free of charge by the CC Consortium.</p>
            
            <div class="warning">
                <h2>WARNING!</h2>
                <p>This software stores coins on a typical server and not the RAIDA. This makes it convenient for you to have coins that are synchronized on all of your devices. However, you should not store large amounts of CC on this website and you should use this only to store small amounts of coins that you plan to move around.</p>
            </div>
            
            <p>Use the <a href="https://cloudcoinconsortium.com/use.php" class="link">CloudCoin Desktop</a> to store large amounts of coins.</p>
        </div>
    </div>

