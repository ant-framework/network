<?php

namespace Amp\Dns;

/**
 * Retrieve the application-wide dns resolver instance
 *
 * @param \Amp\Dns\Resolver $assign Optionally specify a new default dns resolver instance
 * @return \Amp\Dns\Resolver Returns the application-wide dns resolver instance
 */
function resolver(Resolver $assign = null) {
    static $resolver;
    if ($assign) {
        return ($resolver = $assign);
    } elseif ($resolver) {
        return $resolver;
    } else {
        return $resolver = driver();
    }
}
/**
 * Create a new dns resolver best-suited for the current environment
 *
 * @return \Amp\Dns\Resolver
 */
function driver() {
    return new DefaultResolver;
}

/**
 * Resolve a hostname name to an IP address
 * [hostname as defined by RFC 3986]
 *
 * Upon success the returned promise resolves to an indexed array of the form:
 *
 *  [string $recordValue, int $type, int $ttl]
 *
 * A null $ttl value indicates the DNS name was resolved from the cache or the
 * local hosts file.
 * $type being one constant from Amp\Dns\Record
 *
 * Options:
 *
 *  - "server"       | string   Custom DNS server address in ip or ip:port format (Default: 8.8.8.8:53)
 *  - "timeout"      | int      DNS server query timeout (Default: 3000ms)
 *  - "hosts"        | bool     Use the hosts file (Default: true)
 *  - "reload_hosts" | bool     Reload the hosts file (Default: false), only active when no_hosts not true
 *  - "cache"        | bool     Use local DNS cache when querying (Default: true)
 *  - "types"        | array    Default: [Record::A, Record::AAAA] (only for resolve())
 *  - "recurse"      | bool     Check for DNAME and CNAME records (always active for resolve(), Default: false for query())
 *
 * If the custom per-request "server" option is not present the resolver will
 * use the first nameserver in /etc/resolv.conf or default to Google's public
 * DNS servers on Windows or if /etc/resolv.conf is not found.
 *
 * @param string $name The hostname to resolve
 * @param array  $options
 * @return \Amp\Promise
 * @TODO add boolean "clear_cache" option flag
 */
function resolve($name, array $options = []) {
    return resolver()->resolve($name, $options);
}
/**
 * Query specific DNS records.
 *
 * @param string $name Unlike resolve(), query() allows for requesting _any_ name (as DNS RFC allows for arbitrary strings)
 * @param int|int[] $type Use constants of Amp\Dns\Record
 * @param array $options @see resolve documentation
 * @return \Amp\Promise
 */
function query($name, $type, array $options = []) {
    return resolver()->query($name, $type, $options);
}