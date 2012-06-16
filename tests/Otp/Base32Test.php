<?php
require_once __DIR__ . '/../../src/Otp/Base32.php';

require_once 'PHPUnit/Framework/TestCase.php';

use Otp\Base32;

/**
 * Base32 test case.
 */
class Base32Test extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		
		parent::tearDown();
	}
	
	/**
	 * Tests Base32->generateRandom()
	 *
	 * Should always output a string of 16 Characters and only containing
	 * the base32 chars
	 */
	public function testGenerateRandom()
	{
		// Defaults to 16 chars
		$this->assertEquals(16, strlen(Base32::generateRandom()));
	
		// Can be told to make a longer secret
		$this->assertEquals(18, strlen(Base32::generateRandom(18)));
	
		// contains numbers 2-7 and letters A-Z in large letters, 16 chars long
		$this->assertRegExp('/[2-7A-Z]{16}/', Base32::generateRandom());
	}
	
	/**
	 * Tests Base32->decode()
	 *
	 * Testing test vectors according to RFC 4648
	 * http://www.ietf.org/rfc/rfc4648.txt
	 */
	public function testDecode()
	{
		// RFC test vectors say that empty string returns empty string
		$this->assertEquals('', Base32::decode(''));
		
		// these strings are taken from the RFC
		$this->assertEquals('f',      Base32::decode('MY======'));
		$this->assertEquals('fo',     Base32::decode('MZXQ===='));
		$this->assertEquals('foo',    Base32::decode('MZXW6==='));
		$this->assertEquals('foob',   Base32::decode('MZXW6YQ='));
		$this->assertEquals('fooba',  Base32::decode('MZXW6YTB'));
		$this->assertEquals('foobar', Base32::decode('MZXW6YTBOI======'));
	}
	
	/**
	 * Encoder tests, reverse of the decodes
	 */
	public function testEncode()
	{
		// RFC test vectors say that empty string returns empty string
		$this->assertEquals('', Base32::encode(''));
		
		// these strings are taken from the RFC
		$this->assertEquals('MY======',         Base32::encode('f'));
		$this->assertEquals('MZXQ====',         Base32::encode('fo'));
		$this->assertEquals('MZXW6===',         Base32::encode('foo'));
		$this->assertEquals('MZXW6YQ=',         Base32::encode('foob'));
		$this->assertEquals('MZXW6YTB',         Base32::encode('fooba'));
		$this->assertEquals('MZXW6YTBOI======', Base32::encode('foobar'));
	}

}
