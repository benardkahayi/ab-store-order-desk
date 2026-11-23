<?php
// Copy this file to ai_config.php and set the address of your AB STORE AI server
// (https://github.com/benardkahayi/ab-store-ai).
//
// Local testing: run the AI with   uvicorn app.main:app --port 8000
// Production:    deploy the AI (Render, Railway, a VPS...) and put its public HTTPS URL here.
return [
    'url'     => 'http://127.0.0.1:8000',
    'timeout' => 60,   // seconds to wait for an answer
];
