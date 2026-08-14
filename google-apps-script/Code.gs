/**
 * BigLeap Quote Form — Google Apps Script
 * Bind this script to your Google Sheet (Extensions → Apps Script)
 *
 * After pasting this file:
 *   1. Save (Ctrl+S)
 *   2. Run setupSheet  (select it in the function dropdown → Run)
 *   3. Deploy → Manage deployments → Edit (pencil) → Version: New version → Deploy
 */

var RECIPIENT_EMAIL = 'saneshbigleap@gmail.com';
var SHEET_HEADERS = [
  'Timestamp',
  'Full Name',
  'Email',
  'Mobile',
  'Message',
  'Company Name',
  'Services',
  'Website'
];

function getSheet_() {
  return SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
}

function setupSheet() {
  ensureHeaders_(getSheet_());
}

function ensureHeaders_(sheet) {
  var headerRange = sheet.getRange(1, 1, 1, SHEET_HEADERS.length);
  headerRange.setValues([SHEET_HEADERS]).setFontWeight('bold');
  sheet.setFrozenRows(1);
  sheet.getRange('D:D').setNumberFormat('@');
  sheet.getRange('H:H').setNumberFormat('@');
}

function testSubmission() {
  var result = handleSubmission_({
    firstName: 'Test User',
    email: 'test@example.com',
    mobile: '+971 50 000 0000',
    company: 'Acme Studios',
    website: 'https://www.example.com',
    service: '3D Animation',
    message: 'Test from Apps Script — delete this row after checking.'
  });
  Logger.log(result.getContent());
}

function doGet(e) {
  try {
    if (e && e.parameter && e.parameter.firstName) {
      return handleSubmission_(e.parameter);
    }
    return jsonResponse_({ status: 'ok', service: 'BigLeap Quote Form' });
  } catch (err) {
    return jsonResponse_({ success: false, error: String(err) });
  }
}

function doPost(e) {
  try {
    return handleSubmission_(parsePayload_(e));
  } catch (err) {
    return jsonResponse_({ success: false, error: String(err) });
  }
}

function handleSubmission_(payload) {
  var firstName = sanitize_(payload.firstName);
  var email = sanitize_(payload.email);
  var mobile = sanitize_(payload.mobile);
  var company = sanitize_(payload.company);
  var website = sanitize_(payload.website);
  var service = sanitize_(payload.service);
  var message = sanitize_(payload.message);

  if (!firstName || !email || !mobile || !company) {
    return jsonResponse_({ success: false, error: 'Missing required fields.' });
  }

  var sheet = getSheet_();
  ensureHeaders_(sheet);
  sheet.appendRow([new Date(), firstName, email, '', message, company, service, website]);
  var row = sheet.getLastRow();
  sheet.getRange(row, 4).setNumberFormat('@').setValue(mobile);

  try {
    var site = websiteForEmail_(website);
    MailApp.sendEmail({
      to: RECIPIENT_EMAIL,
      name: 'BigLeap',
      subject: 'New Quote Enquiry — ' + firstName,
      body:
        'New quote enquiry from the BigLeap contact form.\n\n' +
        'Full Name: ' + firstName + '\n' +
        'Email: ' + email + '\n' +
        'Mobile: ' + mobile + '\n' +
        'Company Name: ' + company + '\n' +
        (site ? 'Website: ' + site + '\n' : '') +
        'Services: ' + service + '\n' +
        'Message:\n' + message,
      replyTo: email
    });
  } catch (mailErr) {
    Logger.log('Email skipped: ' + mailErr);
  }

  return jsonResponse_({ success: true });
}

function parsePayload_(e) {
  if (e && e.parameter && e.parameter.firstName) {
    return e.parameter;
  }
  if (e && e.postData && e.postData.contents) {
    try {
      return JSON.parse(e.postData.contents);
    } catch (ignore) {}
  }
  return (e && e.parameter) ? e.parameter : {};
}

function sanitize_(value) {
  return String(value || '').trim();
}

function websiteForEmail_(website) {
  return sanitize_(website);
}

function jsonResponse_(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
