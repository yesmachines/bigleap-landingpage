<?php
/**
 * Quote form configuration
 *
 * EMAIL (required — 2 minutes setup):
 *   1. Open https://web3forms.com
 *   2. Enter: saneshbigleap@gmail.com
 *   3. Copy the Access Key and paste below
 *
 * GOOGLE SHEET (required — 5 minutes setup):
 *   1. Open your Google Sheet → Extensions → Apps Script
 *   2. Paste code from google-apps-script/Code.gs → Save
 *   3. Run setupSheet() then testSubmission()
 *   4. Deploy → New deployment → Web app (Execute as: Me, Access: Anyone)
 *   5. Paste the Web App URL below
 *   6. Open that URL in browser — must show: {"status":"ok","service":"BigLeap Quote Form"}
 */
return [
    'recipient_email' => 'saneshbigleap@gmail.com',

    // Get free key at https://web3forms.com (enter saneshbigleap@gmail.com)
    'web3forms_access_key' => '3c1a4604-1567-4183-aee4-919ff32790de',

    // Google Apps Script Web App URL (handles Google Sheet + backup email)
    'gas_webapp_url' => 'https://script.google.com/macros/s/AKfycbwyhcKJ6xLJkKMX_yTl_5-SSsci-BX5G6OnSRpZsl-NG49gSEHldHHRLfLnQEXBwAUhpw/exec',
];
