THINK TECHNOLOGIES - 10DLC WEBSITE UPDATE

Upload the CONTENTS of this folder to the root directory of thinktechnologies.net.
Keep the site's existing logo.png, video files, favicon files, and other assets in place.

Files included:
- index.html: updated website with the optional SMS consent form
- contact-submit.php: records each submission and emails the inquiry
- privacy-policy/index.html: publicly accessible Privacy Policy
- terms-and-conditions/index.html: publicly accessible Terms and Conditions
- storage/.htaccess: prevents public access to consent records

After uploading, confirm these URLs open publicly:
- https://thinktechnologies.net/
- https://thinktechnologies.net/privacy-policy/
- https://thinktechnologies.net/terms-and-conditions/

FORM RECORDS
The form creates storage/sms-consent-records.csv after the first valid submission.
Keep this file private and backed up as evidence of consent. The storage folder must be
writable by PHP but not publicly browsable. On Apache hosting, the included .htaccess
blocks public access. If your server uses Nginx or another web server, ask your host to
deny public access to the /storage/ directory.

EMAIL
The form sends a copy of each inquiry to info@thinktechnologies.net using PHP mail().
Confirm that this mailbox exists and that your hosting provider has PHP mail enabled.

FINAL TEST
1. Submit the form without a phone number or SMS boxes selected; it should succeed.
2. Submit with a phone number and one consent box selected; it should succeed.
3. Select an SMS box without a phone number; the form should show an error.
4. Verify that the email arrived and that storage/sms-consent-records.csv was created.
