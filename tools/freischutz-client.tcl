#!/usr/bin/env tclsh

#
# CLI client for interacting with Freischutz RESTful APIs
#
# see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
#
# author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
# copyright 2018-present Tobias Dély
# license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
#

package require cmdline
package require http
package require tls
package require sha256
package require json

proc hawk_build {id key url method type data ext verbose} {
    regexp {^(http|https)://([^:/]+)(:([0-9]+))?(/(.+)?)?} $url matched protocol host x port uri x

    if {[string length $port] == 0} {
        if {[string equal $protocol "https"]} {
            set port 443
        } elseif {[string equal $protocol "http"]} {
            set port 80
        } else {
            puts "Unknown protocol specified: $protocol"
            exit 1
        }
    }

    set time [clock seconds]

    set payload "hawk.1.payload\n"
    append payload "$type\n"
    append payload "$data\n"

    set hash [binary encode base64 [::sha2::sha256 -bin $payload]]

    set nonce ""
    set chars "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"
    for {set i 0} {$i < 6} {incr i} {
       set range [expr {[string length $chars]-1}]
       set pos [expr {int(rand()*$range)}]
       append nonce [string range $chars $pos $pos]
    }

    set msg "hawk.1.header\n"
    append msg "$time\n"
    append msg "$nonce\n"
    append msg "$method\n"
    append msg "$uri\n"
    append msg "$host\n"
    append msg "$port\n"
    append msg "$hash\n"
    append msg "$ext\n"

    set mac [binary encode base64 [::sha2::hmac -bin -key $key $msg]]

    if {$verbose} {
        puts "-------------------------------------------"
        puts -nonewline $payload
        puts "-------------------------------------------"
        puts -nonewline $msg
        puts "-------------------------------------------"
        puts "MAC:\n$mac\n"
    }

    set header "Hawk id=\"$id\", ts=\"$time\", nonce=\"$nonce\", "
    append header "mac=\"$mac\", hash=\"$hash\", alg=\"sha256\""

    return $header
}

proc basic_auth_build {id key} {
    set encoded [binary encode base64 "$id:$key"]
    return "Basic $encoded"
}

set parameters {
    {hawk                    "use Hawk authentication"}
    {basic                   "use basic authentication"}
    {bearer.arg ""           "use bearer token authentication"}
    {id.arg     ""           "authentication ID"}
    {key.arg    ""           "authentication key"}
    {method.arg "GET"        "HTTP request method"}
    {type.arg   "text/plain" "HTTP request content type"}
    {data.arg   " "          "HTTP request data"}
    {ext.arg    ""           "optional ext value for Hawk"}
    {verbose                 "show HTTP info"}
}

set usage "\[options\] \<url\>"
if {[catch {array set options [::cmdline::getoptions ::argv $parameters $usage]}]} {
    puts [::cmdline::usage $parameters $usage]
    exit 0
}

if {[llength $argv] != 1} {
    puts "Missing URL"
    puts [::cmdline::usage $parameters $usage]
    exit 1
}

set url [lindex $argv 0]

regexp {^(http|https)://} $url matched protocol
if {[string equal $protocol {}]} {
    puts "Unknown protocol specified"
    exit 1
}

if {$options(verbose)} {
    puts "Request details:"
}

set headers {}
if {$options(hawk)} {
    if {[string equal $options(id) {}] || [string equal $options(key) {}]} {
        puts "Hawk requires -id and -key to be set "
    }
    lappend headers Authorization [hawk_build $options(id) $options(key) $url $options(method) $options(type) $options(data) $options(ext) $options(verbose)]
} elseif {$options(basic)} {
    if {[string equal $options(id) {}] || [string equal $options(key) {}]} {
        puts "Basic authentication requires -id and -key to be set "
    }
    lappend headers Authorization [basic_auth_build $options(id) $options(key)]
} elseif {![string equal $options(bearer) {}]} {
    lappend headers Authorization $options(bearer)
}

::http::register https 443 ::tls::socket
set token [::http::geturl $url -method $options(method) -type $options(type) -headers $headers -query $options(data)]
upvar #0 $token state

regexp {[0-9]{3}} $state(http) status
set result "{\"status\":\"$status\",\"type\":\"$state(type)\",\"content\":\"$state(body)\"}"

if {$options(verbose)} {
    parray state
    puts ""
}
puts $result
