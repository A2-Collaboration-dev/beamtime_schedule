<?php

use \Exception;

class Sendmail
{
	/**
	 * SMTP RFC standard line ending
	 *
	 * @var string
	 */
	const CRLF = "\r\n";

	/**
	 * Create an instance of the Mail class
	 *
	 * @param
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Destructor for an instance of this Mail class
	 *
	 * @param
	 * @return void
	 */
	public function __destruct() {}

	/**
	 * Validate a given email address, throw an Exception if it's not valid
	 *
	 * @param string $mail
	 * @return boolean
	 */
	public function is_valid($mail)
	{
		$result = filter_var($mail, FILTER_VALIDATE_EMAIL);
		if (!$result)
			throw new Exception("Email address " . $mail . " is not valid!");

		return $result;
	}

	/**
	 * Send a mail to a single user
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param User   $from
	 * @param array  $cc
	 * @return boolean
	 */
	public function send_single($to, $subject, $msg, $from = null, $cc = null)
	{
		// check if the email address is valid
		if (!@$this->is_valid($to))
			return false;

		return $this->send($to, $subject, $msg, $from, $cc);
	}

	/**
	 * Send a mail to a list of users
	 *
	 * @param array $recipients
	 * @param string $subject
	 * @param string $message
	 * @param User   $from
	 * @param array  $cc
	 * @return boolean
	 */
	public function send_multiple($recipients, $subject, $msg, $from = null, $cc = null)
	{
		if (!is_array($recipients))
			return false;
			//throw new Exception("Given $recipients is not an array!");

		// check if the email addresses are valid
		foreach ($recipients as $mail)
			if (!@$this->is_valid($mail))
				return false;

		$to = implode(", ", $recipients);

		return $this->send($to, $subject, $msg, $from, $cc);
	}

	/**
	 * Send a mail to the specified addresses
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param User   $from
	 * @param array  $cc
	 * @return boolean
	 */
	public function send($to, $subject, $msg, $from, $cc)
	{
		$header[] = "MIME-Version: 1.0";
		$pos = strpos($msg, "<");  // check if the message body contains HTML tags
		if ($pos === false)
			$header[] = "Content-type: text/plain; charset=utf-8";
		else
			$header[] = "Content-type: text/html; charset=utf-8";
		$header[] = "X-Mailer: PHP " . phpversion();
		// check if a User object if given and use the information from it
		// if not use generic beamtime scheduler meta data
		if ($from instanceof User) {
			$header[] = "From: \"" . $from->get_full_name() ."\"<" . $from->email . ">";
			$header[] = "Reply-To: \"" . $from->get_full_name() ."\"<" . $from->email . ">";
		} else {
			$header[] = "From: \"A2 Beamtime Scheduler\"<admin@" . Request::getHost() . ">";
			$header[] = "Reply-To: noreply@" . Request::getHost();
		}
		if (is_array($cc))
			$header[] = "Cc: " . implode(", ", $cc);
		$headers = implode(self::CRLF, $header);

		// use wordwrap() if lines are longer than 70 characters [RFC2822: lines should be no more than 78 characters, excluding CRLF]
		$msg = wordwrap($msg, 70, self::CRLF);

		return @mail($to, $subject, $msg, $headers);
	}
}
