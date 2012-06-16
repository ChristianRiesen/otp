<?php

require_once __DIR__ . '/../../src/Otp/GoogleAuthenticator.php';

require_once 'PHPUnit/Framework/TestCase.php';

use Otp\GoogleAuthenticator;

/**
 * GoogleAuthenticator test case.
 */
class GoogleAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Tests getQrCodeUrl
	 */
	public function testGetQrCodeUrl()
	{
		$secret = 'MEP3EYVA6XNFNVNM'; // testing secret
		
		// Standard totp case
		$this->assertEquals(
			'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chld=M|0&chl=otpauth%3A%2F%2Ftotp%2Fuser%40host.com%3Fsecret%3DMEP3EYVA6XNFNVNM',
			GoogleAuthenticator::getQrCodeUrl('totp', 'user@host.com', $secret)
		);
		
		// hotp (include a counter)
		$this->assertEquals(
			'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chld=M|0&chl=otpauth%3A%2F%2Fhotp%2Fuser%40host.com%3Fsecret%3DMEP3EYVA6XNFNVNM%26counter%3D1234',
			GoogleAuthenticator::getQrCodeUrl('hotp', 'user@host.com', $secret, 1234)
		);
		
		// totp, this time with a parameter for chaning the size of the QR
		$this->assertEquals(
				'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chld=M|0&chl=otpauth%3A%2F%2Ftotp%2Fuser%40host.com%3Fsecret%3DMEP3EYVA6XNFNVNM',
				GoogleAuthenticator::getQrCodeUrl('totp', 'user@host.com', $secret, null, array('height' => 300, 'width' => 300))
		);
		
	}
}
