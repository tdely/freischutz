#!/bin/bash

#
# Simple CLI client for interacting with Freischutz RESTful APIs
#
# see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
#
# author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
# copyright 2017-present Tobias Dély
# license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
#

set -o errexit
set -o pipefail
set -o nounset

VERSION='0.3.0'

content_type='text/plain'
method='GET'
algorithm='sha256'
id=''
key=''
data=''
ext=''
token=''
basic_auth=false
hawk=false
bearer=false
extra_header=''
verbose=false
target=''

time=$(date +%s)

function display_help()
{
    echo "$(basename ${BASH_SOURCE[0]}) -m <STR> [-d <JSON>][-t] <STR> TARGET"
    echo "    -a STR    hash algorithm to use, default sha256"
    echo "    -B        use basic authentication"
    echo "    -c STR    content-type, default text/plain"
    echo "    -d STR    data/payload to send"
    echo "    -e STR    optional ext value for Hawk"
    echo "    -H        use Hawk authentication"
    echo "    -h        print this help message and exit"
    echo "    -i INT    user id to use with request"
    echo "    -k STR    key/password for authentication"
    echo "    -m STR    http request method to use"
    echo "    -t        display timing info:"
    echo "               - response time: time from request until first HTTP response byte received"
    echo "               - operation time: time from request until last HTTP response byte received"
    echo "    -T STR    use bearer token authentication"
    echo "    -V        verbose output"
    echo "    -v        print version and exit"
}

function display_version()
{
    echo "$(basename ${BASH_SOURCE[0]}) version ${VERSION}"
}

function hawk_build()
{
    # Parse target
    local proto=$(echo ${target} | grep -oP "(^https|http)")
    local host=$(echo ${target} | grep -oP "(?<=${proto}:\\/\\/)([^\\/:]+)")
    local port=$(echo ${target} | grep -oP "(?<=${host}:)([^\\/]+)" || true)
    [[ -z "${port}" ]] && port_string='' || port_string=":${port}"
    local uri=$(echo ${target} | grep -oP "(?<=${host}${port_string})(\\/.+)")

    local nonce=$(cat /dev/urandom|tr -dc 'a-zA-Z0-9'|fold -w 6|head -n 1)

    if [ -z "${port}" ]; then
        if [ "${proto}" = 'http' ]; then
            port=80
        elif [ "${proto}" = 'https' ]; then
            port=443
        else
            echo 'unknown protocol specified' >&2
            exit 1
        fi
    fi

    # Build Hawk payload string
    local payload="hawk.1.payload\n"
    payload+="${content_type}\n"
    payload+="${data}\n"

    local payload_hash=$(echo -ne "${payload}"|openssl dgst -${algorithm} -binary|base64 -w0)

    # Build Hawk header string for MAC
    local message="hawk.1.header\n"
    message+="${time}\n"
    message+="${nonce}\n"
    message+="${method}\n"
    message+="${uri}\n"
    message+="${host}\n"
    message+="${port}\n"
    message+="${payload_hash}\n"
    message+="${ext}\n"

    local mac=$(echo -ne ${message}|openssl dgst -${algorithm} -hmac ${key} -binary|base64 -w0)

    if [ ${verbose} = true ]; then
        echo "-------------------------------------------"
        echo -ne "${payload}"
        echo "-------------------------------------------"
        echo -ne "${message}"
        echo "-------------------------------------------"
        echo "MAC:"
        echo -e "${mac}\n"
    fi

    extra_header="Authorization: Hawk id=\"${id}\", ts=\"${time}\", nonce=\"${nonce}\", mac=\"${mac}\", hash=\"${payload_hash}\", alg=\"${algorithm}\""
}

# getopt index variable
OPTIND=1
while getopts ":a:Bc:d:e:Hhi:k:m:tT:Vv" opt; do
    case ${opt} in
        a)
            algorithm="${OPTARG}"
            ;;
        B)
            basic_auth=true
            hawk=false
            bearer=false
            ;;
        c)
            content_type="${OPTARG}"
            ;;
        d)
            data="${OPTARG}"
            ;;
        e)
            ext="${OPTARG}"
            ;;
        H)
            hawk=true
            basic_auth=false
            bearer=false
            ;;
        h)
            display_help
            exit 0
            ;;
        i)
            id="${OPTARG}"
            ;;
        k)
            key="${OPTARG}"
            ;;
        m)
            method="${OPTARG^^}"
            ;;
        t)
            timing="\n\n--TIMING DETAILS\nResponse time:  %{time_starttransfer}\nOperation time:  %{time_total}\n"
            ;;
        T)
            basic_auth=false
            hawk=false
            bearer=true
            token="${OPTARG}"
            ;;
        V)
            verbose=true
            ;;
        v)
            display_version
            exit 0
            ;;
        \?)
            echo "Invalid option: -${OPTARG}" >&2
            display_help
            exit 1
            ;;
        :)
            echo "Option -${OPTARG} requires an argument." >&2
            display_help
            exit 1
            ;;
    esac
done

# Remove all option arguments
shift $(($OPTIND - 1))

if [ -z "${method}" ]; then
    echo "No method specified" >&2
    display_help
    exit 1
fi

if [ "${#}" = 0 ]; then
    echo "No target specified" >&2
    display_help
    exit 1
fi

if [ "${#}" -gt 1 ]; then
    echo "Too many arguments" >&2
    display_help
    exit 1
fi

# Target is first non-option argument
target="${1}"

echo -e "\n--REQUEST DETAILS"

if [ ${basic_auth} = true ]; then
    if [ -z "${id}" ] || [ -z "${key}" ]; then
        echo "Basic authentication requires -i and -k to be set"
    fi
fi

if [ ${hawk} = true ]; then
    if [ -z "${id}" ] || [ -z "${key}" ]; then
        echo "Hawk requires -i and -k to be set"
    fi
    hawk_build
fi

# Use tmp files for payload and formatting for timing
# easiest way since curl is difficult about whitespace
TMP_DATA=$(mktemp)
TMP_FORMAT=$(mktemp)
echo "${data}" > ${TMP_DATA}
echo "${timing:-}" > ${TMP_FORMAT}

# Send HTTP request
echo "--BEGIN CURL"
if [ ${hawk} = true ]; then
    curl -i -w @${TMP_FORMAT} -d @${TMP_DATA} -X "${method}" -H "Content-Type: ${content_type}" -H "${extra_header}" $target
elif [ ${basic_auth} = true ]; then
    curl -i -w @${TMP_FORMAT} -d @${TMP_DATA} -X "${method}" -H "Content-Type: ${content_type}" -u "${id}:${key}" $target
elif [ ${bearer} = true ]; then
    curl -i -w @${TMP_FORMAT} -d @${TMP_DATA} -X "${method}" -H "Content-Type: ${content_type}" -H "Authorization: Bearer ${token}" $target
else
    curl -i -w @${TMP_FORMAT} -d @${TMP_DATA} -X "${method}" -H "Content-Type: ${content_type}" $target
fi

echo -e "\n--END CURL\n"

# Clean up
rm ${TMP_DATA}
rm ${TMP_FORMAT}
