<?php

namespace Otp\Tests;

use Otp\Otp;
use PHPUnit\Framework\TestCase;

/**
 * Otp test case.
 */
class OtpTest extends TestCase
{
	/**
	 *
	 * @var Otp
	 */
	private $Otp;
	
	private $secret = "12345678901234567890";

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$this->Otp = new Otp();
	
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Otp = null;
		
		parent::tearDown();
	}

	/**
	 * Invalid counter values for tests
	 */
	public function hotpTestValues()
	{
		return [
				 ['755224', 0], ['287082', 1], ['359152', 2],
				 ['969429', 3], ['338314', 4], ['254676', 5],
   				 ['287922', 6], ['162583', 7], ['399871', 8],
   				 ['520489', 9]
			   ];
	}

	/**
	 * Invalid counter values for tests
	 */
	public function totpTestValues()
	{
		// https://www.rfc-editor.org/errata_search.php?rfc=6238
		$secretSha1   = '12345678901234567890';
		$secretSha256 = '12345678901234567890123456789012';
		$secretSha512 = '1234567890123456789012345678901234567890123456789012345678901234';

		return [
			['sha1',   $secretSha1,   '94287082', 59],
			['sha1',   $secretSha1,   '07081804', 1111111109],
			['sha1',   $secretSha1,   '14050471', 1111111111],
			['sha1',   $secretSha1,   '89005924', 1234567890],
			['sha1',   $secretSha1,   '69279037', 2000000000],
			['sha1',   $secretSha1,   '65353130', 20000000000],
			['sha256', $secretSha256, '46119246', 59],
			['sha256', $secretSha256, '68084774', 1111111109],
			['sha256', $secretSha256, '67062674', 1111111111],
			['sha256', $secretSha256, '91819424', 1234567890],
			['sha256', $secretSha256, '90698825', 2000000000],
			['sha256', $secretSha256, '77737706', 20000000000],
			['sha512', $secretSha512, '90693936', 59],
			['sha512', $secretSha512, '25091201', 1111111109],
			['sha512', $secretSha512, '99943326', 1111111111],
			['sha512', $secretSha512, '93441116', 1234567890],
			['sha512', $secretSha512, '38618901', 2000000000],
			['sha512', $secretSha512, '47863826', 20000000000],
		];
	}

	/**
	 * Invalid counter values for tests
	 */
	public function invalidCounterValues()
	{
		return [
				 ['a'], [-1]
			   ];
	}

	/**
	 * Invalid counter values for tests
	 */
	public function hotpResyncDefaultTestValues()
	{
		return [
				 ['755224', 0], ['287082', 1], ['359152', 2]
			   ];
	}

	/**
	 * Invalid counter values for tests
	 */
	public function hotpResyncWindowTestValues()
	{
		return [
				 ['969429', 0, 3], ['338314', 0, 4],
				 ['287922', 3, 3], ['162583', 3, 4]
			   ];
	}

	/**
	 * Invalid counter values for tests
	 */
	public function hotpResyncFailureTestValues()
	{
		return [
				 ['287922', 7], ['162583', 8], ['399871', 9]
			   ];
	}
	
	/**
	 * Tests Otp->hotp()
	 *
	 * Using test vectors from RFC
	 * https://tools.ietf.org/html/rfc4226
	 *
	 * @dataProvider hotpTestValues
	 */
	public function testHotpRfc($key, $counter)
	{
		$secret = $this->secret;
	
		$this->assertEquals($key, $this->Otp->hotp($secret, $counter));
	}
		
	/**
	 * Tests TOTP general construction
	 *
	 * Still uses the hotp function, but since totp is a bit more special, has
	 * its own tests
	 * Using test vectors from RFC
	 * https://tools.ietf.org/html/rfc6238
	 *
	 * @dataProvider totpTestValues
	 */
	public function testTotpRfc($algo, $secret, $key, $time)
	{
		// Test vectors are in 8 digits
		$this->Otp->setDigits(8);
		
		// The time presented in the test vector has to be first divided through 30
		// to count as the key

		$this->Otp->setAlgorithm($algo);
		$this->assertEquals($key, $this->Otp->hotp($secret, floor($time/30)), "$algo with $time");
	}

	/**
	 * @dataProvider invalidCounterValues
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid counter supplied
	 */
	public function testHotpInvalidCounter($counter)
	{
		$this->Otp->hotp($this->secret, $counter);
	}

	/**
	 * Tests Otp->checkHotpResync() with default counter window
	 *
	 * @dataProvider hotpResyncDefaultTestValues
	 */
	public function testHotpResyncDefault($key, $counter)
	{
		$secret = $this->secret;

		// test with default counter window
		$this->assertSame($counter, $this->Otp->checkHotpResync($secret, $counter, $key));
	}


	/**
	 * Tests Otp->checkHotpResync() with a provided counter window
	 *
	 * @dataProvider hotpResyncWindowTestValues
	 */
	public function testHotpResyncWindow($key, $counter, $counterwindow)
	{
		$secret = $this->secret;

		// test with provided counter window
		$this->assertSame(($counter + $counterwindow), $this->Otp->checkHotpResync($secret, $counter, $key, $counterwindow));
	}

	/**
	 * Tests Otp->checkHotpResync() with mismatching key and counter
	 *
	 * @dataProvider hotpResyncFailureTestValues
	 */
	public function testHotpResyncFailures($key, $counter)
	{
		$secret = $this->secret;

		// test failures
		$this->assertFalse($this->Otp->checkHotpResync($secret, $counter, $key));
	}

	/**
	 * @dataProvider invalidCounterValues
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid counter supplied
	 */
	public function testHotpResyncInvalidCounter($counter)
	{
		$this->Otp->checkHotpResync($this->secret, $counter, '755224');
	}

	/**
	 * @dataProvider invalidCounterValues
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid counterwindow supplied
	 */
	public function testHotpResyncInvalidCounterWindow($counterwindow)
	{
		$this->Otp->checkHotpResync($this->secret, 0, '755224', $counterwindow);
	}

}
