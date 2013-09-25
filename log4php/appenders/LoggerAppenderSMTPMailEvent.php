<?php
/**
 * LoggerAppenderSMTPMailEvent appends log events individual or not 
 * via email on smtp authenticated with or without TLS
 * 
 * This appender is a conjunction of LoggerAppenderMail and LoggerAppenderMailEvent
 * except that it sends email message over smtp authenticated account.
 * 
 * This appender uses a layout.
 * 
 * ## Configurable parameters: ##
 * 
 * - **to** - Email address(es) to which the log will be sent
 * 			  Multiple email addresses may be specified by separating them with a comma
 * - **from** - Email address which will be used in the From field
 * - **subject** - Subject of the email message
 * - **smtpHost** - The SMTP server to connect
 * - **port** - Used to override the default SMTP server port
 * - **username** - SMTP account username
 * - **password** - SMTP account password
 * - **charset** - The message charset
 *
 * @package log4php
 * @subpackage appenders
 * @author Thiago França < thiagotrue (at) gmail (dot) com >
 */
class LoggerAppenderSMTPMailEvent extends LoggerAppender {

	/**
	 * Carriage return and linefeed
	 */
	const CRLF = "\r\n";

	/**
	 * Smtp host success code
	 * @var array
	 */
	protected $smtpCodeSuccess = array( 221, 235, 250, 251, 334, 354 );

	/**
	 * Smtp port
	 * @var integer
	 */
	protected $port = 25;

	/**
	 * Server host
	 * @var string
	 */
	protected $hostname;

	/**
	 * Smtp host
	 * @var string
	 */
	protected $smtpHost;

	/**
	 * From address
	 * @var string
	 */
	protected $from;

	/**
	 * Recipients 
	 * @var array
	 */
	protected $to = array();

	/**
	 * Carbon copy recipients
	 * @var array
	 */
	protected $cc = array();

	/**
	 * Blind carbon copy recipients
	 * @var array
	 */
	protected $bcc = array();

	/**
	 * Subject of message
	 * @var string
	 */
	protected $subject = 'Log4php Report';

	/**
	 * Smtp account username
	 * @var string
	 */
	protected $username;

	/**
	 * Smtp account password
	 * @var string
	 */
	protected $password;

	/**
	 * StartTLS or not
	 * @var boolean
	 */
	protected $ssl = false;

	/**
	 * Connection timeout in seconds
	 * @var integer
	 */
	protected $timeout = 20;

	/**
	 * The charset of message
	 * @var string
	 */
	protected $charset = 'ISO-8859-1';

	/**
	 * The MIME-Version Header
	 * @var string
	 */
	protected $mimeVersion = '1.0';

	/**
	 * Send mail by single or multiple event
	 * @var boolean
	 */
	protected $single = false;

	/**
	 * Smtp connection resource
	 * @var resource
	 */
	private $smtpConnection;

	/**
	 * The unique ID for message
	 * @var string
	 */
	private $messageID;

	/**
	 * The date of message
	 * @var string
	 */
	private $date;

	/**
	 * The content type of message
	 * @var string
	 */
	private $contentType;

	/**
	 * The body content used to 
	 * send multiples events in one mail 
	 * @var string
	 */
	private $body;

	/**
	 * The content transfer encoding
	 * @var string
	 */
	private $contentTransferEncoding = '8BIT';

	/**
	 * Makes null the smtp connection
	 * and set the message id
	 */
	public function __construct() {

		$this->smtpConnection = null;

		$date = base_convert( microtime(), 10, 36 );
		$uniqid = base_convert( md5( uniqid( rand(), true ) ), 10, 36 );

		$this->messageID = sprintf( '<%s.%s@%s>', $date, $uniqid, $this->getHostname() );

	}

	/**
	 * Set smtp port
	 * 
	 * @param integer $port
	 */
	public function setPort( $port ) {
		$this->setPositiveInteger( 'port', $port );
	}

	/**
	 * Return smtp port
	 * 
	 * @return number
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * Set smtp host
	 * 
	 * @param string $smtpHost
	 */
	public function setSmtpHost( $smtpHost ) {
		$this->setString( 'smtpHost', $smtpHost );
	}

	/**
	 * Return smtp host
	 * 
	 * @return string
	 */
	public function getSmtpHost() {
		return $this->smtpHost;
	}

	/**
	 * Set mail from address
	 * 
	 * @param string $from
	 */
	public function setFrom( $from ) {
		$this->setString( 'from', $from );
	}

	/**
	 * Return mail from address
	 * 
	 * @return string
	 */
	public function getFrom() {
		return $this->from;
	}

	/**
	 * Set recipient or a list of recipients
	 * 
	 * @param mixed string/array $to
	 */
	public function setTo( $to ) {
		$this->addRecipient( $to, 'to' );
	}

	/**
	 * Return a list of recipients
	 * 
	 * @return array
	 */
	public function getTo() {
		return $this->to;
	}

	/**
	 * Set carbon copy recipient or a list of recipients
	 * 
	 * @param string/array $cc
	 */
	public function setCc( $cc ) {
		$this->addRecipient( $cc, 'cc' );
	}

	/**
	 * Return carbon copy recipients
	 * 
	 * @return array
	 */
	public function getCc() {
		return $this->cc;
	}

	/**
	 * Set blind carbon copy recipient or a list of recipients
	 * 
	 * @param string/array $bcc
	 */
	public function setBcc( $bcc ) {
		$this->addRecipient( $bcc, 'bcc' );
	}

	/**
	 * Return blind carbon copy recipients
	 * 
	 * @return array
	 */
	public function getBcc() {
		return $this->bcc;
	}

	/**
	 * Set message subject
	 * 
	 * @param string
	 */
	public function setSubject( $subject ) {
		$this->setString( 'subject',  $subject );
	}

	/**
	 * Return the message subject
	 * 
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Set and encode smtp username
	 * 
	 * @param string $username
	 */
	public function setUsername( $username ) {
		$this->setString( 'username', base64_encode($username) );
	}

	/**
	 * Return encoded smtp username
	 * 
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Set and encode smtp password
	 * 
	 * @param string $password
	 */
	public function setPassword( $password ) {
		$this->setString( 'password', base64_encode($password) );
	}

	/**
	 * Return encoded smtp password
	 * 
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Enable/Disable SSL
	 * 
	 * @param string/boolean $ssl
	 */
	public function setSsl( $ssl ) {
		$this->setBoolean( 'ssl', ( $ssl === 'tls' ) );
	}

	/**
	 * Return true if ssl is enabled otherwise false
	 * 
	 * @return boolean
	 */
	public function getSsl() {
		return $this->ssl;
	}

	/**
	 * Set timeout connection in seconds
	 * 
	 * @param integer $timeout
	 */
	public function setTimeout( $timeout ) {
		$this->setPositiveInteger( 'timeout', $timeout );
	}

	/**
	 * Return timeout connection in seconds
	 * 
	 * @return number
	 */
	public function getTimeout() {
		return $this->timeout;
	}

	/**
	 * Set the message charset
	 * 
	 * @param string $charset
	 */
	public function setCharset( $charset ) {
		$this->setString( 'charset', $charset );
	}

	/**
	 * Return the message charset
	 * 
	 * @return string
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Connect to smtp host
	 * 
	 * @return boolean
	 */
	protected function connect() {

		if( $this->isConnected() ) {
			$this->warn( 'Already connected to a server' );
			$this->closed = true;
			return false;
		}

		try {
			$this->smtpConnection = fsockopen( $this->smtpHost, $this->port, $errno, $errstr, $this->timeout );
			$this->getLines(); // clear socket output
		} catch( Exception $e ) {
			$this->warn( 'Failed to connect to server' );
			$this->closed = true;
			return false;
		}

		if( substr( PHP_OS, 0, 3 ) != 'WIN' && function_exists( 'stream_set_timeout' ) )
			stream_set_timeout( $this->smtpConnection, $this->timeout, 0 );

		return true;

	}

	/**
	 * Disconnect from server 
	 */
	protected function disconnect() {

		if( $this->smtpConnection ) {

			fclose( $this->smtpConnection );

			$this->smtpConnection = null;

		}

	}

	/**
	 * Return a well formed string to message header
	 * 
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	protected function getHeaderLine( $name, $value ) {

		if( !empty( $name ) )
			return ucfirst($name) . ': ' . $value . "\n";
		else
			return $value . "\n";

	}

	/**
	 * Return a well formed string to recipients
	 * on message header (to, cc and bcc)
	 * 
	 * @param string $addressType - An address type ( to, cc, bcc )
	 * @param array $addressList - A list of recipients
	 * @return string
	 */
	protected function getAddressHeaderLine( $addressType, Array $addressList ) {

		if( !in_array( $addressType, array( 'to', 'cc', 'bcc' ) ) )
			return;

		$header = '';

		if( !empty( $addressList ) )
			$header = implode( '>,<', $addressList );

		if( !empty( $header ) )
			return $this->getHeaderLine( ucfirst($addressType), "<{$header}>" );

	}

	/* 
	 * Forwards the logging event to the destination.
	 * 
	 * @param LoggerLoggingEvent $event
	 */
	protected function append( LoggerLoggingEvent $event ) {

		$this->body .= $this->layout->format($event);

		if( $this->single )
			$this->close();

	}

	/* 
	 * Close the appender and send the email
	 */
	public function close() {

		if( !$this->smtpConnect() )
			return false;

		if( !$this->mail() )
			return false;

		if( !$this->checkRecipes() )
			return false;

		$mail  = $this->getHeaderLine( 'message-ID' , $this->messageID );
		$mail .= $this->getHeaderLine( 'date', date( 'r' ) );
		$mail .= $this->getHeaderLine( 'subject', $this->getEncodedSubject() );
		$mail .= $this->getHeaderLine( 'from', $this->from );

		$mail .= $this->getAddressHeaderLine( 'to', $this->to );
		$mail .= $this->getAddressHeaderLine( 'cc', $this->cc );
		$mail .= $this->getAddressHeaderLine( 'bcc', $this->bcc );

		$mail .= $this->getHeaderLine( 'MIME-Version', $this->mimeVersion );
		$mail .= $this->getHeaderLine( 'Content-Type', "{$this->layout->getContentType()}; charset={$this->charset}" );
		$mail .= $this->getHeaderLine( 'Content-Transfer-Encoding', $this->contentTransferEncoding );
		$mail .= self::CRLF;

		$mail .= $this->layout->getHeader();
		$mail .= $this->body;
		$mail .= $this->layout->getFooter();
		$mail .= self::CRLF. self::CRLF . '.';

		if( !$this->data( $mail ) )
			return false;

		$this->quit();

		$this->closed = true;

	}

	/**
	 * Returns a encoded subject for the message
	 */
	protected function getEncodedSubject() {

		$subject = mb_convert_encoding( $this->subject, $this->charset, 'auto' );

		return mb_encode_mimeheader( $subject, $this->charset, 'Q' );

	}

	/**
	 * @return array
	 */
	protected function checkRecipes() {

		$badRecipes = array();

		if( is_array( $this->to ) && !empty( $this->to ) )
			$badRecipes = array_merge( $badRecipes, $this->addRecipients( $this->to ) );

		if( is_array( $this->cc ) && !empty( $this->cc ) )
			$badRecipes = array_merge( $badRecipes, $this->addRecipients( $this->cc ) );

		if( is_array( $this->bcc ) && !empty( $this->bcc ) )
			$badRecipes = array_merge( $badRecipes, $this->addRecipients( $this->bcc ) );

		if( count( $badRecipes ) > 0 ) {
			$badRecipes = implode( ', ', $badRecipes );
			$this->warn( 'Bad recipients to send: ' . $badRecipes );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/* 
	 * Prepares the appender for logging
	 */
	public function activateOptions() {

		parent::activateOptions();

		if( empty( $this->smtpHost ) ) {
			$this->warn( 'Required parameter \'smtpHost\' not set. Closing appender.' );
			$this->closed = true;
			return;
		}

		if( empty( $this->username ) ) {
			$this->warn( 'Required paramter \'username\' not set. Closing appender.' );
			$this->closed = true;
			return;
		}

		if( empty( $this->password ) ) {
			$this->warn( 'Required parameter \'password\' not set. Closing appender.' );
			$this->closed = true;
			return;
		}

		if( empty( $this->from ) ) {
			$this->warn( 'Required parameter \'from\' not set. Closing appender.' );
			$this->closed = true;
			return;
		}

		if( empty( $this->to ) ) {
			$this->warn( 'Required parameter \'to\' not set. Closing appender.' );
			$this->closed = true;
			return;
		}

		$this->closed = false;

	}

	/**
	 * Add recipient/recipients to list identified by recipe type
	 * 
	 * @param string/array $recipient
	 * @param string $recipeType
	 * @return boolean
	 */
	protected function addRecipient( $recipient, $type = 'to' ) {

		if( !in_array( $type, array( 'to', 'cc', 'bcc' ) ) )
			return false;

		if( !is_array( $recipient ) ) {

			if( preg_match( '/,/', $recipient ) )
				$recipient = explode( ',', preg_replace( '/\s*/', '', $recipient ) );
			else
				$recipient = array( $recipient );

		}

		$this->{$type} = $recipient;

	}

	/**
	 * Write recipients into socket
	 * 
	 * @param array $recipients
	 * @return array 
	 */
	protected function addRecipients( Array $recipients ) {

		$badRecipes = array();

		foreach( $recipients as $recipe ) {

			if( !$this->isValid($recipe) || !$this->recipient( $recipe ) )
				$badRecipes[] = $recipe;

		}

		return $badRecipes;

	}

	/**
	 * Check if recipient is valid address
	 * 
	 * @param string $recipient
	 * @return boolean
	 */
	protected function isValid( $recipient ) {

		if( !is_string($recipient) )
			return false;

		$regex = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/";

		return preg_match( $regex, (string)$recipient );

	}

	/**
	 * Returns true if connected to a server otherwise false
	 * 
	 * @return bool
	 */
	protected function isConnected() {

		// check if connector is a resource
		if( is_resource( $this->smtpConnection ) ) {

			$socket = socket_get_status( $this->smtpConnection );

			// connected
			if( !$socket['eof'] )
				return true;

			$this->warn( 'EOF caught while checking if connected' );

			$this->disconnect();

			return false;

		}

		return false;

	}

	/**
	 * Start TLS
	 * 
	 * @return boolean
	 */
	protected function startTLS() {

		if( !$this->isConnected() ) {
			$this->warn( 'Called startTLS without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( 'STARTTLS' );

		$response = $this->getLines();

		if( $this->getCode($response) != 220 ) {
			$this->warn( 'STARTTLS not accepted from server' );
			$this->closed = true;
			return false;
		}

		if( !stream_socket_enable_crypto( $this->smtpConnection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT ) ) {
			$this->warn( 'Crypto not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Authenticate in smtp host
	 * 
	 * @return boolean
	 */
	protected function authenticate() {

		$this->write( 'AUTH LOGIN' );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'AUTH not accepted from server' );
			$this->closed = true;
			return false;
		}

		if( !$this->authUsername() )
			return false;

		if( !$this->authPassword() )
			return false;

		return true;

	}

	/**
	 * authenticate username
	 * 
	 * @return boolean
	 */
	protected function authUsername() {

		$this->write( $this->username );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'Username not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * authenticate password
	 * 
	 * @return boolean
	 */
	protected function authPassword() {

		$this->write( $this->password );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'Password not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Write to server
	 * 
	 * @param string $string
	 * @return boolean
	 */
	protected function write( $string ) {

		try {
			fwrite( $this->smtpConnection, $string . self::CRLF );
		} catch( Exception $e ) {
			$this->warn( 'An error ocurred on write in handle' );
			$this->closed = true;
			return false;
		}

	}

	/**
	 * Return the code part from message
	 * 
	 * @param numeric $message
	 * @return boolean
	 */
	protected function getCode( $message ) {

		if( !empty( $message ) )
			return substr( $message, 0, 3 );

		return false;

	}

	/**
	 * Write envelop body
	 * 
	 * @param string $data
	 * @return boolean
	 */
	protected function data( $data ) {

		if( !$this->isConnected() ) {
			$this->warn( 'Called data without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( 'DATA' );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'DATA command not accepted from server' );
			$this->closed = true;
			return false;
		}

		$this->writeMessage( $data );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'DATA not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Normalize string new lines
	 * and send to writeLine method
	 * 
	 * @param string $data
	 */
	protected function writeMessage( $data ) {

		$data = preg_replace( array( '/\\r\\n/', '/\\r/' ), "\n", $data );

		$lines = explode( "\n", $data );

		$field = substr( $lines[0], 0, strpos( $lines[0], ':' ) );

		$headers = false;

		if( !empty( $field ) && !strstr( $field, ' ' ) )
			$headers = true;

		foreach( $lines as $line )
			$this->writeLine( $line, $headers );

	}

	/**
	 * Split string into $limit length
	 * and write to server
	 * 
	 * @param string $line
	 * @param boolean $headers
	 */
	protected function writeLine( $line, &$headers ) {

		$limit = 998;

		if( empty( $line ) && $headers )
			$headers = false;

		while( strlen( $line ) > $limit ) {

			$pos = strrpos( substr( $line, 0, $limit ), ' ' );

			$line = substr( $line, (!$pos ? $limit-1 : $pos+1) );

			if( $headers )
				$line = "\t{$line}";

			if( strlen( $line ) > 0 && substr( $line, 0, 1 ) == '.' )
				$line = '.' . $line;

			$this->write( $line );

		}

		$this->write( $line );

	}

	/**
	 * Say Hello to server
	 * 
	 * @param string $hostname
	 * @return boolean
	 */
	protected function hello( $hostname = '' ) {

		if( !$this->isConnected() ) {
			$this->warn ( 'Called hello without being connected' );
			$this->closed = true;
			return false;
		}

		if( empty( $hostname ) )
			$hostname = 'localhost';

		if( !$this->sayHello( 'EHLO', $hostname ) ) {
			if( !$this->sayHello( 'HELO', $hostname ) ) {
				$this->warn( 'EHLO/HELO not accepted from server' );
				$this->closed = true;
				return false;
			}
		}

		return true;

	}

	/**
	 * Say hello hostname
	 * 
	 * @param string $hello
	 * @param string $hostname
	 * @return boolean
	 */
	protected function sayHello( $hello, $hostname ) {

		$this->write( $hello . ' ' . $hostname );

		$response = $this->getLines();

		if( !$this->isSuccess($response) )
			return false;

		return true;

	}
	
	/**
	 * Send the mail from to server
	 * 
	 * @param string $from
	 * @return boolean
	 */
	protected function mail() {

		if( !$this->isConnected() ) {
			$this->warn( 'Called mail without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( "MAIL FROM:<{$this->from}>" );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'MAIL not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Send quit command to server
	 * 
	 * @param boolean $closeOnError
	 * @return boolean
	 */
	protected function quit( $closeOnError = true ) {

		if( !$this->isConnected() ) {
			$this->warn( 'Called quit without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( 'QUIT' );

		$error = false;

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'SMTP server rejected quit command' );
			$error = true;
		}

		if( $error && $closeOnError ) {
			$this->closed = true;
		}

		return !$error;

	}

	/**
	 * Send recipients (RCPT TO) to server
	 * 
	 * @param string $to
	 * @return boolean
	 */
	protected function recipient( $to ) {

		if( !$this->isConnected() ) {
			$this->warn( 'Called recipient without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( "RCPT TO:<{$to}>" );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'RCPT not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Clear the server's envelope
	 * 
	 * @return boolean
	 */
	protected function reset() {

		if( !$this->isConnected() ) {
			$this->warn( 'Called reset without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( 'RSET' );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'RSET failed' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Requests delivery to the 'terminal' if that 
	 * is available and to the mailbox
	 * 
	 * @param string $from
	 * @return boolean
	 */
	protected function send() {

		if( !$this->isConnected() ) {
			$this->warn( 'Called send without being connected' );
			$this->closed = true;
			return false;
		}

		$this->write( 'SAML FROM:' . $this->from );

		$response = $this->getLines();

		if( !$this->isSuccess($response) ) {
			$this->warn( 'SAML not accepted from server' );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Return all lines from message
	 * 
	 * @return string
	 */
	protected function getLines() {

		$data = '';

		while( $str = fgets( $this->smtpConnection, 515 ) ) {

			$data .= $str;

			if( substr( $str, 3, 1 ) == ' ' )
				break;

		}

		return $data;

	}

	/**
	 * Connect to server 
	 * and say hello
	 * 
	 * @return boolean
	 */
	protected function smtpConnect() {

		try {

			while( !$this->isConnected() ) {

				if( $this->connect() ) {

					$this->hello( $this->getHostname() );

					if ( $this->ssl ) {

						if( !$this->startTLS() ) {
							$this->closed = true;
							return false;
						}

						$this->hello( $this->getHostname() );

					}

				}

			}

			if( !$this->authenticate() ) {
				$this->closed = true;
				return false;
			}

		} catch( Exception $e ) {
			$this->reset();
			$this->warn( $e->getMessage() );
			$this->closed = true;
			return false;
		}

		return true;

	}

	/**
	 * Check if message returned success
	 * 
	 * @param string $responseCode
	 */
	protected function isSuccess( $response ) {

		$responseCode = $this->getCode( $response );

		return in_array( $responseCode, $this->smtpCodeSuccess );

	}

	/**
	 * Return the hostname
	 * 
	 * @return string
	 */
	protected function getHostname() {

		if( !empty( $this->hostname ) )
			return $this->hostname;

		if( isset( $_SERVER['SERVER_NAME'] ) )
			return $_SERVER['SERVER_NAME'];

		return 'localhost.localdomain';

	}

}