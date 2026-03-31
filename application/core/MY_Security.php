<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Security extends CI_Security
{
	/**
	 * Get random bytes (open_basedir-safe)
	 *
	 * @param	int	$length	Output length
	 * @return	string|bool
	 */
	public function get_random_bytes($length)
	{
		if (empty($length) OR ! ctype_digit((string) $length))
		{
			return FALSE;
		}

		// First option to avoid touching /dev/urandom when open_basedir blocks it.
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$output = openssl_random_pseudo_bytes($length);
			if ($output !== FALSE)
			{
				return $output;
			}
		}

		if (defined('MCRYPT_DEV_URANDOM') && ($output = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)) !== FALSE)
		{
			return $output;
		}

		if (@is_readable('/dev/urandom') && ($fp = @fopen('/dev/urandom', 'rb')) !== FALSE)
		{
			// Try not to waste entropy ...
			is_php('5.4') && stream_set_chunk_size($fp, $length);
			$output = fread($fp, $length);
			fclose($fp);
			if ($output !== FALSE)
			{
				return $output;
			}
		}

		return FALSE;
	}
}
