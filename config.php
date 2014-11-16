<?php

/*
 * User Path
 * All files ending in .php in the following path will be included.
 * This is where you would put your callback functions.
 */
	$user_path = './user';

/*
 * Delay
 * We will check the status of the hosts every "delay" seconds.  For
 * a small number of hosts, or when stilldown/stillup callbacks are
 * not defined, this can be every second.  But if you have a large
 * number of hosts, or have complex stilldown/stillup callback
 * functions, running this too frequently could bog down the system.
 *
 * Also note that if one or more hosts are down, it can take some time
 * to timeout; or if callback functions take a measurable amount of time
 * to run, the delay can be longer than what's specified.  To be exact,
 * the delay indicated below is the number of seconds from one group of
 * checks ending until the next one begins; so it's the amount of idle
 * time between checking the last host of a set and running any callback
 * functions, and when the next host of a set begins checking.
 */
	$delay = 5;

/*
 * Log Level
 * There are a couple of log levels available:
 * debug  - Log all notices generated.
 * normal - Log only changes in status, such as a host going up or down.
 * none   - Do not make any log entries.
 */
	$log_level = 'normal';

/*
 * Hosts
 * In the following array, define your hosts and callback functions
 * for each.  There are multiple callback functions available, but none
 * of them are required.  However, you should specify at least one
 * call-back function, otherwise running this script on the host is
 * kind of pointless.
 *
 * Here are the options:
 * host -
 *   The name or IP address of the host we're monitoring.  If it's a
 *   name, note that this is dependent on DNS operating properly, and
 *   if this script cannot resolve the name, the host will be
 *   considered down.
 *
 * Callback Functions :
 *   down -
 *     The function to call when the host was up but is now down.  This
 *     function will also be called if when the script first runs and
 *     the host is down the first time it's checked.
 *   up -
 *     The function to call when the host was down but is now up.
 *     Unlike callback_down, this function will not run the first time
 *     if the host is up.  It is assumed that the host is up when the
 *     script first runs.
 *   stilldown -
 *     The function to call when the host was last down, and is still
 *     down for the current check.
 *   stillup -
 *     The function to call when the host was last up, and is still up
 *     for the current check.
 *
 * ping_send -
 *   The number of ICMP packets to send to the host.  If this value is
 *   not specified, it defaults to 1.
 * ping_receive -
 *   The number of ICMP packets that must be received in order to say
 *   that the host is up.  If this value is not set, it defaults to 1.
 *   If this value exceeds ping_send, it is set to be the same as
 *   ping_send.  In most cases, this should be set to the same as
 *   ping_send.  But if the system running this program and the host
 *   being monitored are across a high packet-loss link, you may want to
 *   consider the host up even if some packets are dropped.
 * ping_timeout -
 *   The timeout in milliseconds for an individual ping to wait for a
 *   response.  Setting this value low will allow more real-time
 *   checking because we will be spending less time waiting for a
 *   response and works on a LAN.  But setting it too low across a high
 *   latency link may inadvertantly mark the host as down.
 */
	$hosts = array(
		array(
			'host'     => '10.54.40.36',
			'callback' => array(
				'down'         => 'down',
				'up'           => 'up',
				'stilldown'    => 'stilldown',
				'stillup'      => 'stillup'
			),
			'ping_send'    => 5,
			'ping_receive' => 5,
			'ping_timeout' => 1000
		),
		array(
			'host'     => '192.168.1.128',
			'callback' => array(
				'down'         => 'down',
				'up'           => 'up',
				'stilldown'    => 'stilldown',
				'stillup'      => 'stillup'
			),
			'ping_send'    => 5,
			'ping_receive' => 5,
			'ping_timeout' => 1000
		)
	);

?>
