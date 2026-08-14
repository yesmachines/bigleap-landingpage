/**
 * BigLeap Quote Form — Google Apps Script
 * Bind this script to your Google Sheet (Extensions → Apps Script)
 */

var RECIPIENT_EMAIL = 'saneshbigleap@gmail.com';

function getSheet_() {
  return SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
}

function setupSheet() {
  var sheet = getSheet_();
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(['Timestamp', 'Full Name', 'Email', 'Mobile', 'Message', 'Service', 'Company']);
    sheet.getRange(1, 1, 1, 7).setFontWeight('bold');
    sheet.setFrozenRows(1);
  }
  // Force Mobile column to plain text (prevents +971... showing as #ERROR!)
  sheet.getRange('D:D').setNumberFormat('@');
}

function testSubmission() {
  var result = handleSubmission_({
    firstName: 'Test User',
    email: 'test@example.com',
    mobile: '+971 50 000 0000',
    company: 'Acme Studios',
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
  var service = sanitize_(payload.service);
  var message = sanitize_(payload.message);

  if (!firstName || !email || !mobile || !company) {
    return jsonResponse_({ success: false, error: 'Missing required fields.' });
  }

  setupSheet();
  var sheet = getSheet_();
  if (!sheet.getRange(1, 6).getValue()) {
    sheet.getRange(1, 6).setValue('Service').setFontWeight('bold');
  }
  if (!sheet.getRange(1, 7).getValue()) {
    sheet.getRange(1, 7).setValue('Company').setFontWeight('bold');
  }
  sheet.appendRow([new Date(), firstName, email, '', message, service, company]);
  var row = sheet.getLastRow();
  sheet.getRange(row, 4).setNumberFormat('@').setValue(mobile);

  try {
    MailApp.sendEmail({
      to: RECIPIENT_EMAIL,
      subject: 'New Quote Enquiry — ' + firstName,
      body:
        'New quote enquiry from BigLeap website.\n\n' +
        'Full Name: ' + firstName + '\n' +
        'Email: ' + email + '\n' +
        'Mobile: ' + mobile + '\n' +
        'Company: ' + company + '\n' +
        'Service: ' + service + '\n' +
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

function jsonResponse_(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
