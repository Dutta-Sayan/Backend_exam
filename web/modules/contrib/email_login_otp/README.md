Email Login OTP module for Drupal 8.x & 9.x.
This module adds Email based OTP authentication functionality to Drupal.

INSTALLATION INSTRUCTIONS
-------------------------

1.  Download the module and unzip it your Drupal /modules/ or /modules/contrib/ directory.
2.  Enable the module:
    a.  Login as site administrator, visit the Extend page, and enable Email Login OTP.
    b.  Run "drush pm-enable email_login_otp" on the command line.
3.  No configurations needed.
4.  Done!

NOTES
-----
* This module provides OTP authentication to Login form only.
* This module overrides the default Login form submit callback and registers its' own ajax based callback.
* Generated OTP is valid til 5 minutes.
* No configrations needed.
