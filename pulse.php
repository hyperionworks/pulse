#!/usr/bin/php
<?php

// Include config file
	include( __DIR__ . '/config.php' );

// See if we need to daemonize or if we're running interactive
	if ( isset( $argv[1] ) && $argv[1] == '--daemon' ) {
		$daemon = TRUE;
		daemonize();

		pcntl_signal( SIGTERM, 'signal_handler' );
		pcntl_signal( SIGINT,  'signal_handler' );
		pcntl_signal( SIGCHLD, 'signal_handler' );

	} else {
		$daemon = FALSE;
	}

	openlog( 'pulse', LOG_PID, LOG_DAEMON );

// Include user code in user_path
	if ( isset( $user_path )) {
		notify( 'normal', FALSE, "Reading in user-defined code from $user_path...\n" );

		foreach ( glob( $user_path . '/*.php' ) as $include ) {
			if ( substr( $include, -4 ) == '.php' ) {
				include( $include );
			}
		}
	} else {
		notify( 'error', TRUE, "$user_path is undefined -- There's nothing for us to do.\n" );
	}

// Set host definitions and callback functions
	if ( isset( $hosts ) && is_array( $hosts )) {
		notify( 'normal', FALSE, "Setting up monitored hosts...\n" );
		$monitored_hosts = array();

		foreach ( $hosts as $i => $host ) {
			notify( 'normal', FALSE, "  Host: {$host['host']}\n" );

			$monitored_hosts[ $i ] = new host( $host['host'] );

			if ( !empty( $host['callback']['down'] )) {
				$monitored_hosts[ $i ]->set_callback_down( $host['callback']['down'] );
			}

			if ( !empty( $host['callback']['stilldown'] )) {
				$monitored_hosts[ $i ]->set_callback_stilldown( $host['callback']['stilldown'] );
			}

			if ( !empty( $host['callback']['stillup'] )) {
				$monitored_hosts[ $i ]->set_callback_stillup( $host['callback']['stillup'] );
			}

			if ( !empty( $host['callback']['up'] )) {
				$monitored_hosts[ $i ]->set_callback_up( $host['callback']['up'] );
			}

			if ( !empty( $host['ping_send'] )) {
				$monitored_hosts[ $i ]->set_ping_send( $host['ping_send'] );
			} else {
				$monitored_hosts[ $i ]->set_ping_send( 1 );
			}

			if ( !empty( $host['ping_receive'] ) && $host['ping_receive'] <= $monitored_hosts[ $i ]->ping_send ) {
				$monitored_hosts[ $i ]->set_ping_receive( $host['ping_receive'] );
			} else {
				$monitored_hosts[ $i ]->set_ping_receive( 1 );
			}

			if ( !empty( $host['ping_timeout'] )) {
				$monitored_hosts[ $i ]->set_ping_timeout( $host['ping_timeout'] );
			} else {
				$monitored_hosts[ $i ]->set_ping_timeout( 1000 );
			}
		}
	} else {
		notify( 'error', TRUE, "You haven't defined any hosts -- There's nothing for us to do.\n" );
	}

	notify( 'normal', FALSE, "Starting monitor.\n" );

	while ( TRUE ) {
	// Be sure we can catch signals
		pcntl_signal_dispatch();

	// Check all hosts
		foreach ( $monitored_hosts as $monitored_host ) {
			$monitored_host->check();
		}

	// Delay before next check
		sleep( $delay );
	}

// Begin Class

	class host {
	/*
	 * Host
	 * Checks a host to see if it's up or down, and runs the specified
	 * callback funciton.
	 */

		var $host   = '';
		var $status = 'up';

		var $callback_down      = '';
		var $callback_stilldown = '';
		var $callback_stillup   = '';
		var $callback_up        = '';

		var $ping_send    = 1;
		var $ping_receive = 1;
		var $ping_timeout = 1000;

		function __construct( $host = '' ) {
		/*
		 * Create our class variable placeholders.
		 */

			if ( !empty( $host )) {
				$this->host = $host;
			}
		}

		function check() {
		/*
		 * Check
		 * Checks if a host is up or down, and executes the specified
		 * callback function.
		 */

			$up = 0;

			for ( $i = 0; $i < $this->ping_send; ++$i ) {
				if ( ping( $this->host, $this->ping_timeout )) {
					++$up;
				}
			}

			$sent     = $this->ping_send;
			$received = $up;

			if ( $up < $this->ping_receive ) {
			// Host is down
				if ( $this->status == 'down' ) {
				// Host was down before
					if ( !empty( $this->callback_stilldown )) {
						notify( 'debug', FALSE, "{$this->host} is still down, running callback. Sent: $sent, Received: $received\n" );

						call_user_func( $this->callback_stilldown, $this->host );
					} else {
						notify( 'debug', FALSE, "{$this->host} is still down, no callback defined. Sent: $sent, Received: $received\n" );
					}
				} else {
				// Host just now went down
					if ( !empty( $this->callback_down )) {
						notify( 'normal', FALSE, "{$this->host} is down, running callback. Sent: $sent, Received: $received\n" );

						call_user_func( $this->callback_down, $this->host );
					} else {
						notify( 'normal', FALSE, "{$this->host} is down, no callback defined. Sent: $sent, Received: $received\n" );
					}
				}

				$this->status = 'down';
			} else {
			// Host is up
				if ( $this->status == 'up' ) {
				// Host was up before
					if ( !empty( $this->callback_stillup )) {
						notify( 'debug', FALSE, "{$this->host} is still up, running callback. Sent: $sent, Received: $received\n" );

						call_user_func( $this->callback_stillup, $this->host );
					} else {
						notify( 'debug', FALSE, "{$this->host} is still up, no callback defined. Sent: $sent, Received: $received\n" );
					}
				} else {
				// Host just now came back up
					if ( !empty( $this->callback_up )) {
						notify( 'normal', FALSE, "{$this->host} is up, running callback. Sent: $sent, Received: $received\n" );

						call_user_func( $this->callback_up, $this->host );
					} else {
						notify( 'normal', FALSE, "{$this->host} is up, no callback defined. Sent: $sent, Received: $received\n" );
					}
				}

				$this->status = 'up';
			}
		}

		function set_callback_down( $function ) {
		/*
		 * Set Callback Down
		 * The function to call when the host is down after being up.
		 */

			$this->callback_down = $function;
		}

		function set_callback_stilldown( $function ) {
		/*
		 * Set Callback Still Down
		 * The function to call when the host has been tested as down
		 * and it was down before.
		 */

			$this->callback_stilldown = $function;
		}

		function set_callback_stillup( $function ) {
		/*
		 * Set Callback Still Up
		 * The function to call when the host has been tested as up,
		 * and it was up before.
		 */

			$this->callback_stillup = $function;
		}

		function set_callback_up( $function ) {
		/*
		 * Set Callback Up
		 * The function to call when the host is up after going down.
		 */

			$this->callback_up = $function;
		}

		function set_host( $host ) {
		/*
		 * Set Host
		 * Sets the host to monitor.
		 */

			$this->host = $host;
		}

		function set_ping_receive( $ping_receive ) {
		/*
		 * Set Ping Receive
		 * Sets the number of pings we need to receive in order to count
		 * the host as up.
		 */

			$this->ping_receive = $ping_receive;
		}

		function set_ping_send( $ping_send ) {
		/*
		 * Set Ping Send
		 * Sets the number of pings to send.
		 */

			$this->ping_send = $ping_send;
		}

		function set_ping_timeout( $ping_timeout ) {
		/*
		 * Set Ping Timeout
		 * Sets the timeout in milliseconds to wait for the ping to complete.
		 */

			$this->ping_timeout = $ping_timeout;
		}
	}

	function daemonize() {
	/*
	 * Become Daemon
	 * Become a daemon by forking and closing the parent
	 */

		global $daemon;

		$pid = pcntl_fork();

		if ( $pid == -1 ) {
		// Fork failed
			notify( 'error', TRUE, "Fork failed!\n" );
		} elseif ( $pid ) {
		// close the parent
			exit();
		} else {
		// Child becomes our daemon
			$daemon = TRUE;

			posix_setsid();
			umask( 0 );
			return posix_getpid();
		}
	}

	function notify( $level, $exit, $text ) {
	/*
	 * Print notifications to STDOUT in interactive mode.  Log message
	 * according to log level.
	 */
		global $daemon, $log_level;

	// Check to see if we're running in interactive mode so we can
	// output the error to STDOUT
		if ( !$daemon ) {
			echo date( 'Y-m-d H:i:s' ) . " - $text";
		}

	// Check logging settings and levels to see if we need to log this
	// message to syslog
		if ( $level == 'debug' && $log_level == 'debug' ) {
			syslog( LOG_DEBUG, $text );
		} elseif ( $level == 'normal' && $log_level != 'none' ) {
			syslog( LOG_INFO, $text );
		} elseif ( $level == 'error' && $log_level != 'none' ) {
			syslog( LOG_ERR, $text );
		}

		if ( $exit ) {
			exit( 1 );
		}
	}

	function ping( $host, $timeout = 1000 ) {
	/*
	 * Ping
	 * Sends an ICMP ping packet to the destination host waiting for
	 * the specified timeout in milliseconds.
	 */

	// Put together package to send
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";

	// Open the socket
		$socket = socket_create( AF_INET, SOCK_RAW, 1 );

	// Set timeout
		$sec  = floor( $timeout / 1000 );
		$usec = ( $timeout % 1000 ) * 1000;

		socket_set_option( $socket, SOL_SOCKET, SO_RCVTIMEO, array( 'sec' => $sec, 'usec' => $usec ));

	// Connect
		socket_connect( $socket, $host, NULL );

	// Get start time
		$ts = microtime( TRUE );

	// Send the ping
		socket_send( $socket, $package, strlen( $package ), 0 );

	// Get the result
		if( socket_read( $socket, 255 )) {
			$result = microtime( TRUE ) - $ts;
		} else {
			$result = FALSE;
		}

	// Close the socket
		socket_close( $socket );

	// Return the result
		return $result;
	}

	function signal_handler( $sig ) {
	/*
	 * Sig Handler
	 * Handle signals
	 */
		switch ( $sig ) {
			case SIGTERM:
			case SIGINT:
				notify( 'normal', TRUE, "...program terminated!\n" );
			break;

			case SIGCHLD:
				pcntl_waitpid( -1, $status );
			break;
		}
	}

?>
