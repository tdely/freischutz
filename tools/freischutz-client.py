#!/usr/bin/env python
# -*- encoding: utf-8 -*-

"""
CLI client for interacting with Freischutz RESTful APIs
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

:see: https://gitlab.com/tdely/freischutz/ Freischutz on GitLab

:author: Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
:copyright: (c) 2018-present Tobias Dély.
:licence: https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause

"""

import argparse
import re
import sys
import time
import base64
import hashlib
import hmac
import random
import string
import requests

def hawk_build(uid, key, url, method, ctype, data='', alg='', ext='', verbose=False):
    """
    Build Hawk authentication header

    :param uid: Hawk client ID
    :param key: Hawk client key
    :param url: HTTP request URL
    :param method: HTTP request method
    :param ctype: HTTP request content type
    :param data: HTTP request data
    :param alg: Hawk hash algorithm
    :param ext: Hawk ext
    :param verbose:
    :returns: Header content
    """
    try:
        crypto = getattr(hashlib, alg)
    except AttributeError as e:
        print("Unsupported hash algorithm '{}', available: '{}'.".format(
            alg,
            "', '".join(hashlib.algorithms_available)
        ))
        sys.exit(1)

    matches = re.match(r'^(http|https)://([^:/]+)(:([0-9]+))?(/(.+)?)?', url)
    (protocol, host, x, port, uri, x) = matches.groups()
    del x

    if port is None:
        if protocol == "https":
            port = 443
        elif protocol == "http":
            port = 80
        else:
            print('Unknown protocol specified: {}'.format(protocol))
            sys.exit(1)

    ts = int(time.time())

    payload = "hawk.1.payload\n"
    payload += "{}\n"
    payload += "{}\n"
    payload = payload.format(ctype, data)

    payload_hash = base64.b64encode(crypto(payload).digest())

    nonce = ''.join(
        random.choice(string.ascii_lowercase + string.digits) for _ in range(6)
    )

    msg = "hawk.1.header\n"
    msg += "{}\n"
    msg += "{}\n"
    msg += "{}\n"
    msg += "{}\n"
    msg += "{}\n"
    msg += "{}\n"
    msg += "{}\n"
    msg += "{}\n"
    msg = msg.format(ts, nonce, method, uri, host, port, payload_hash, ext)

    mac = base64.b64encode(hmac.new(key, msg, crypto).digest())

    if verbose:
        print("-------------------------------------------\n"
              "{}"
              "-------------------------------------------\n"
              "{}"
              "-------------------------------------------\n"
              "MAC:\n{}\n".format(payload, msg, mac))

    header = 'Hawk id="{}", ts="{}", nonce="{}", mac="{}", hash="{}", alg="{}"'.format(
        uid, ts, nonce, mac, payload_hash, alg
    )

    return header


def basic_auth_build(uid, key):
    """
    Build basic authentication header.

    :param uid: Username
    :param key: Password
    :returns: Header content
    """
    return 'Basic {}'.format(base64.b64encode('{}:{}'.format(uid, key)))


def main():
    """Main"""
    argparser = argparse.ArgumentParser(
        description=''
    )
    argparser.add_argument('-a', '--algorithm', metavar='STR', dest='alg',
                           action='store', type=str, default='sha256',
                           help='hash algorithm to use for Hawk, default sha256')
    argparser.add_argument('-B', '--basic', dest='basic', action='store_true',
                           help='use basic authentication')
    argparser.add_argument('-H', '--hawk', dest='hawk', action='store_true',
                           help='use Hawk authentication')
    argparser.add_argument('-T', '--bearer', metavar='STR', dest='bearer',
                           action='store', type=str,
                           help='use bearer token authentication')
    argparser.add_argument('-i', '--id', metavar='STR', dest='id',
                           action='store', type=str, help='authentication ID')
    argparser.add_argument('-k', '--key', metavar='STR', dest='key',
                           action='store', type=str, help='authentication key')
    argparser.add_argument('-c', '--content-type', metavar='STR', dest='type',
                           action='store', type=str, default='text/plain',
                           help='HTTP request content type, default text/plain')
    argparser.add_argument('-d', '--data', metavar='STR', dest='data',
                           action='store', type=str, default='',
                           help='HTTP request data')
    argparser.add_argument('-e', '--ext', metavar='STR', dest='ext',
                           action='store', type=str, default='',
                           help='optional ext value for Hawk')
    argparser.add_argument('-m', '--method', metavar='STR', dest='method',
                           action='store', type=str, default='GET',
                           help='HTTP request method')
    argparser.add_argument('-V', '--verbose', dest='verbose', action='store_true',
                           help='show HTTP info')
    argparser.add_argument('url')
    args = argparser.parse_args()

    headers = {'Content-Type': args.type}
    if args.hawk:
        if not args.id or not args.key:
            print('Hawk requires -id and -key to be set')
            sys.exit(1)
        headers['Authorization'] = hawk_build(
            args.id,
            args.key,
            args.url,
            args.method,
            args.type,
            args.data,
            args.alg,
            args.ext,
            args.verbose
        )
    if args.basic:
        if not args.id or not args.key:
            print('Basic authentication requires -id and -key to be set')
            sys.exit(1)
        headers['Authorization'] = basic_auth_build(args.id, args.key)
    if args.bearer:
        headers['Authorization'] = args.bearer

    matches = re.match(r'^(http|https)://([^:/]+)(:([0-9]+))?(/(.+)?)?', args.url)
    (protocol, host, x, port, uri, x) = matches.groups()
    del x

    response = requests.request(args.method, args.url, data=args.data, headers=headers)
    result = {
        'status': response.status_code,
        'type': response.headers['content-type'],
        'content': response.content
    }

    print(result)


if __name__ == '__main__':
    sys.exit(main())
