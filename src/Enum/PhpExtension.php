<?php

declare(strict_types=1);

namespace App\Enum;

enum PhpExtension: string
{
    use UtilityTrait;

    case APCU = 'apcu';

    case BCMATH = 'bcmath';
    case BZ2 = 'bz2';
    case CALENDAR = 'calendar';
    case CORE = 'core';
    case CTYPE = 'ctype';
    case CURL = 'curl';
    case DATE = 'date';
    case DBA = 'dba';
    case DOM = 'dom';
    case EXIF = 'exif';
    case FILEFINFO = 'fileinfo';
    case FILTER = 'filter';
    case FTP = 'ftp';
    case GD = 'gd';
    case GETTEXT = 'gettext';
    case GMP = 'gmp';
    case HASH = 'hash';
    case ICONV = 'iconv';
    case IMAGICK = 'imagick';
    case IMAP = 'imap';
    case INTL = 'intl';
    case JSON = 'json';
    case LDAP = 'ldap';
    case LIBXML = 'libxml';
    case MBSTRING = 'mbstring';
    case MEMCACHE = 'memcache';
    case MEMCACHED = 'memcached';
    case MONGODB = 'mongodb';
    case MYSQLI = 'mysqli';
    case MYSQLND = 'mysqlnd';
    case OPENSSL = 'openssl';
    case PCNTL = 'pcntl';
    case PCRE = 'pcre';
    case PDO = 'pdo';
    case PDO_MYSQL = 'pdo_mysql';
    case PDO_PGSQL = 'pdo_pgsql';
    case PDO_SQLLITE = 'pdo_sqlite';
    case PGSQL = 'pgsql';
    case PHALCON = 'phalcon';
    case PHAR = 'Phar';
    case POSIX = 'posix';
    case RANDOM = 'random';
    case READLINE = 'readline';
    case REDIS = 'redis';
    case REFLECTION = 'Reflection';
    case SCWS = 'scws';
    case SESSION = 'session';
    case SHMOP = 'shmop';
    case SIMPLEXML = 'SimpleXML';
    case SOAP = 'soap';
    case SOCKETS = 'sockets';
    case SODIUM = 'sodium';
    case SPL = 'SPL';
    case SQLITE3 = 'sqlite3';
    case STANDARD = 'standard';
    case SWOOLE = 'swoole';
    case SYSVSEM = 'sysvsem';
    case SYSVSHM = 'sysvshm';
    case TIDY = 'tidy';
    case TOKENIZER = 'tokenizer';
    case XDEBUG = 'xdebug';
    case XML = 'xml';
    case XMLREADER = 'xmlreader';
    case XMLWRITER = 'xmlwriter';
    case XSL = 'xsl';
    case OPCACHE = 'opcache';
    case ZIP = 'zip';
    case ZLIB = 'zlib';
}
