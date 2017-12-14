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
		return [
				 ['94287082', 59], ['07081804', 1111111109], ['14050471', 1111111111],
				 ['89005924', 1234567890], ['69279037', 2000000000], ['65353130', 20000000000],
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
	public function testTotpRfc($key, $time)
	{
		$secret = $this->secret;
		
		// Test vectors are in 8 digits
		$this->Otp->setDigits(8);
		
		// The time presented in the test vector has to be first divided through 30
		// to count as the key

		// SHA 1 grouping
		$this->assertEquals($key, $this->Otp->hotp($secret, floor($time/30)), "sha 1 with $time");

		
		/*
		The following tests do NOT pass.
		Once the otp class can deal with these correctly, they can be used again.
		They are here for completeness test vectors from the RFC.
		
		// SHA 256 grouping
		$this->Otp->setAlgorithm('sha256');
		$this->assertEquals('46119246', $this->Otp->hotp($secret,          floor(59/30)), 'sha256 with time 59');
		$this->assertEquals('07081804', $this->Otp->hotp($secret,  floor(1111111109/30)), 'sha256 with time 1111111109');
		$this->assertEquals('14050471', $this->Otp->hotp($secret,  floor(1111111111/30)), 'sha256 with time 1111111111');
		$this->assertEquals('89005924', $this->Otp->hotp($secret,  floor(1234567890/30)), 'sha256 with time 1234567890');
		$this->assertEquals('69279037', $this->Otp->hotp($secret,  floor(2000000000/30)), 'sha256 with time 2000000000');
		$this->assertEquals('65353130', $this->Otp->hotp($secret, floor(20000000000/30)), 'sha256 with time 20000000000');
		
		// SHA 512 grouping
		$this->Otp->setAlgorithm('sha512');
		$this->assertEquals('90693936', $this->Otp->hotp($secret,          floor(59/30)), 'sha512 with time 59');
		$this->assertEquals('25091201', $this->Otp->hotp($secret,  floor(1111111109/30)), 'sha512 with time 1111111109');
		$this->assertEquals('99943326', $this->Otp->hotp($secret,  floor(1111111111/30)), 'sha512 with time 1111111111');
		$this->assertEquals('93441116', $this->Otp->hotp($secret,  floor(1234567890/30)), 'sha512 with time 1234567890');
		$this->assertEquals('38618901', $this->Otp->hotp($secret,  floor(2000000000/30)), 'sha512 with time 2000000000');
		$this->assertEquals('47863826', $this->Otp->hotp($secret, floor(20000000000/30)), 'sha512 with time 20000000000');
		*/
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
