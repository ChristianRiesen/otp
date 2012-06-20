One Time Passwords
==================

[![Build Status](https://secure.travis-ci.org/ChristianRiesen/otp.png)](http://travis-ci.org/ChristianRiesen/otp)

Did you like this? Flattr it:

[![Flattr otp](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/thing/719284/ChristianRiesenotp-on-GitHub)

Usage
-----

```php
<?php

use Otp\Otp;
use Otp\GoogleAuthenticator;

// Seperate class, see https://github.com/ChristianRiesen/base32
use Base32\Base32;

// Get a Pseudo Secret
// Defaults to 16 characters
$secret = GoogleAuthenticator::generateRandom();

// Url for the QR code
// Using totp method
$url = GoogleAuthenticator::getQrCodeUrl('totp', 'Label like user@host.com', $secret);

// Save the secret with the users account
// Display QR Code to the user

// Now how to check
$otp = new Otp();

// $key is a 6 digit number, coming from the User
// Assuming this is present and sanitized
// Allows for some timedrift (2 keys before and
// 2 keys after the present one)
if ($otp->checkTotp(Base32::decode($secret), $key)) {
    // Correct key
    // IMPORTANT! Note this key as being used
    // so nobody could launch a replay attack.
    // Cache that for the next minutes and you
    // should be good.
} else {
    // Wrong key
}

// Just to create a key for display (testing)
$key = $otp->totp($secret);

```

Sample script in `example` folder. Requires sessions to work (for secret storage).

Class Otp
---------

Implements hotp according to [RFC4226](https://tools.ietf.org/html/rfc4226) and totp according to [RFC6238](https://tools.ietf.org/html/rfc6238) (only sha1 algorithm). Once you have a secret, you can use it directly in this class to create the passwords themselves (mainly for debugging use) or use the check functions to safely check the validity of the keys. The `checkTotp` function also includes a helper to battle timedrift.

Class Base32
------------

Helper class to supply [RFC4648](http://www.ietf.org/rfc/rfc4648.txt) conform base32 encoding and decoding.

Static functions for decode and encode. Additional helper function to generate a pseudorandom that can be used with GoogleAuthenticator.


Class GoogleAuthenticator
-------------------------

Single static function to generate a correct url for the QR code, so you can easy scan it with your device. Google Authenticator is opensource and avaiaible as application for iPhone and Android. This removes the burden to create such an app from the developers of websites by using this set of classes.

About
=====

Requirements
------------

PHP 5.3.x+

If you want to run the tests, PHPUnit 3.6 or up is required.

Author
------

Christian Riesen <chris.riesen@gmail.com> http://christianriesen.com

Acknowledgements
----------------

The classes have been inspired by many different places that were talking about otp and Google Authenticator. Thank you all for your help.

Base32 is mostly based on the work of https://github.com/NTICompass/PHP-Base32

Otp is a cumulation of work from many different places, optimized, cleaned up and brought into a testable form.

Project setup ideas blantently taken from https://github.com/Seldaek/monolog