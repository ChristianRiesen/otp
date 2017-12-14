<?php

namespace Otp;

/**
 * One Time Passwords
 *
 * Implements HOTP and TOTP
 *
 * HMAC-Based One-time Password(HOTP) algorithm specified in RFC 4226
 * @link https://tools.ietf.org/html/rfc4226
 *
 * Time-based One-time Password (TOTP) algorithm specified in RFC 6238
 * @link https://tools.ietf.org/html/rfc6238
 *
 * As a note: This code is NOT 2038 proof! The min concern is the function
 * getBinaryCounter that uses the pack function which can't handle 64bit yet.
 *
 * Can be easy used with Google Authenticator
 * @link https://code.google.com/p/google-authenticator/
 *
 * @author Christian Riesen <chris.riesen@gmail.com>
 * @link http://christianriesen.com
 * @license MIT License see LICENSE file
 */

class Otp implements OtpInterface
{
    /**
     * The digits the code can have
     *
     * Either 6 or 8.
     * Authenticator does only support 6.
     *
     * @var integer
     */
    protected $digits = 6;
    
    /**
     * Time in seconds one counter period is long
     *
     * @var integer
     */
    protected $period = 30;
    
    /**
     * Possible algorithms
     *
     * @var array
     */
    protected $allowedAlgorithms = array('sha1', 'sha256', 'sha512');
    
    /**
     * Currently used algorithm
     *
     * @var string
     */
    protected $algorithm = 'sha1';

    /**
     * Time offset between system time and GMT in seconds
     *
     * @var integer
     */
    protected $totpOffset = 0;
    
    /* (non-PHPdoc)
     * @see Otp.OtpInterface::hotp()
    */
    public function hotp($secret, $counter)
    {
        if (!is_numeric($counter) || $counter < 0) {
            throw new \InvalidArgumentException('Invalid counter supplied');
        }
        
        $hash = hash_hmac(
                $this->algorithm,
                $this->getBinaryCounter($counter),
                $secret,
                true
        );
    
        return str_pad($this->truncate($hash), $this->digits, '0', STR_PAD_LEFT);
    }
    
    /* (non-PHPdoc)
     * @see Otp.OtpInterface::totp()
    */
    public function totp($secret, $timecounter = null)
    {
        if (is_null($timecounter)) {
            $timecounter = $this->getTimecounter();
        }
    
        return $this->hotp($secret, $timecounter);
    }
    
    /* (non-PHPdoc)
     * @see Otp.OtpInterface::checkHotp()
    */
    public function checkHotp($secret, $counter, $key)
    {
        return hash_equals($this->hotp($secret, $counter), $key);
    }


    /* (non-PHPdoc)
     * @see Otp.OtpInterface::checkHotpResync()
    */
    public function checkHotpResync($secret, $counter, $key, $counterwindow = 2)
    {
        if (!is_numeric($counter) || $counter < 0) {
            throw new \InvalidArgumentException('Invalid counter supplied');
        }

        if(!is_numeric($counterwindow) || $counterwindow < 0){
            throw new \InvalidArgumentException('Invalid counterwindow supplied');
        }

        for($c = 0; $c <= $counterwindow; $c = $c + 1) {
            if(hash_equals($this->hotp($secret, $counter + $c), $key)){
                return $counter + $c;
            }
        }
        return false;
    }
    
    /* (non-PHPdoc)
     * @see Otp.OtpInterface::checkTotp()
    */
    public function checkTotp($secret, $key, $timedrift = 1)
    {
        if (!is_numeric($timedrift) || $timedrift < 0) {
            throw new \InvalidArgumentException('Invalid timedrift supplied');
        }
        // Counter comes from time now
        // Also we check the current timestamp as well as previous and future ones
        // according to $timerange
        $timecounter = $this->getTimecounter();
    
        $start = $timecounter - ($timedrift);
        $end = $timecounter + ($timedrift);
    
        // We first try the current, as it is the most likely to work
        if (hash_equals($this->totp($secret, $timecounter), $key)) {
            return true;
        } elseif ($timedrift == 0) {
            // When timedrift is 0, this is the end of the checks
            return false;
        }
    
        // Well, that didn't work, so try the others
        for ($t = $start; $t <= $end; $t = $t + 1) {
            if ($t == $timecounter) {
                // Already tried that one
                continue;
            }
                
            if (hash_equals($this->totp($secret, $t), $key)) {
                return true;
            }
        }
    
        // if none worked, then return false
        return false;
    }
    
    /**
     * Changing the used algorithm for hashing
     *
     * Can only be one of the algorithms in the allowedAlgorithms property.
     *
     * @param string $algorithm
     * @throws \InvalidArgumentException
     * @return \Otp\Otp
     */
    
    /*
     * This has been disabled since it does not bring the expected results
     * according to the RFC test vectors for sha256 or sha512.
     * Until that is fixed, the algorithm simply stays at sha1.
     * Google Authenticator does not support sha256 and sha512 at the moment.
     *
    
    public function setAlgorithm($algorithm)
    {
        if (!in_array($algorithm, $this->allowedAlgorithms)) {
            throw new \InvalidArgumentException('Not an allowed algorithm: ' . $algorithm);
        }
        
        $this->algorithm = $algorithm;
        
        return $this;
    }
    // */
    
    /**
     * Get the algorithms name (lowercase)
     *
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }
    
    /**
     * Setting period lenght for totp
     *
     * @param integer $period
     * @throws \InvalidArgumentException
     * @return \Otp\Otp
     */
    public function setPeriod($period)
    {
        if (!is_int($period)) {
            throw new \InvalidArgumentException('Period must be an integer');
        }
        
        $this->period = $period;
        
        return $this;
    }
    
    /**
     * Returns the set period value
     *
     * @return integer
     */
    public function getPeriod()
    {
        return $this->period;
    }
    
    /**
     * Setting number of otp digits
     *
     * @param integer $digits Number of digits for the otp (6 or 8)
     * @throws \InvalidArgumentException
     * @return \Otp\Otp
     */
    public function setDigits($digits)
    {
        if (!in_array($digits, array(6, 8))) {
            throw new \InvalidArgumentException('Digits must be 6 or 8');
        }
        
        $this->digits = $digits;
        
        return $this;
    }
    
    /**
     * Returns number of digits in the otp
     *
     * @return integer
     */
    public function getDigits()
    {
        return $this->digits;
    }

    /**
     * Set offset between system time and GMT
     *
     * @param integer $offset GMT - time()
     * @throws \InvalidArgumentException
     * @return \Otp\Otp
     */
    public function setTotpOffset($offset)
    {
        if (!is_int($offset)) {
            throw new \InvalidArgumentException('Offset must be an integer');
        }
        
        $this->totpOffset = $offset;
        
        return $this;
    }
    
    /**
     * Returns offset between system time and GMT in seconds
     *
     * @return integer
     */
    public function getTotpOffset()
    {
        return $this->totpOffset;
    }
    
    /**
     * Generates a binary counter for hashing
     *
     * Warning: Not 2038 safe. Maybe until then pack supports 64bit.
     *
     * @param integer $counter Counter in integer form
     * @return string Binary string
     */
    private function getBinaryCounter($counter)
    {
        return pack('N*', 0) . pack('N*', $counter);
    }
    
    /**
     * Generating time counter
     *
     * This is the time divided by 30 by default.
     *
     * @return integer Time counter
     */
    private function getTimecounter()
    {
        return floor((time() + $this->totpOffset) / $this->period);
    }
    
    /**
     * Creates the basic number for otp from hash
     *
     * This number is left padded with zeros to the required length by the
     * calling function.
     *
     * @param string $hash hmac hash
     * @return number
     */
    private function truncate($hash)
    {
        $offset = ord($hash[19]) & 0xf;
        
        return (
            ((ord($hash[$offset+0]) & 0x7f) << 24 ) |
            ((ord($hash[$offset+1]) & 0xff) << 16 ) |
            ((ord($hash[$offset+2]) & 0xff) << 8 ) |
            (ord($hash[$offset+3]) & 0xff)
            ) % pow(10, $this->digits);
    }
}

