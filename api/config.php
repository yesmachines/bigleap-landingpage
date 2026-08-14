<?php
/**
 * Quote form configuration
 *
 * EMAIL (required — 2 minutes setup):
 *   1. Open https://web3forms.com
 *   2. Enter: marketing@bigleap.ae
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
    'recipient_email' => 'marketing@bigleap.ae',

    // Get free key at https://web3forms.com (enter marketing@bigleap.ae)
    'web3forms_access_key' => 'YOUR_WEB3FORMS_KEY_HERE',

    // Google Apps Script Web App URL (handles Google Sheet + backup email)
    'gas_webapp_url' => 'https://script.google.com/macros/s/AKfycbxu2HZSYIofSNqAB3tqVE6IdUpb181UuU8aBET7m0Um56SGUX3g1-t_5VR7lbSIklCATQ/exec',
];
