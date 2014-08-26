# Version 0.7.8

## Bugfixes

* None

## Features

* Add static (no dynamic) request handler and servlet engine version

# Version 0.7.7

## Bugfixes

* Make TTL for request handler random between 10 and 50 seconds

## Features

* Move HttpSessionWrapper implementation from TechDivision_ServletEngine to this library

# Version 0.7.6

## Bugfixes

* Bugfix invalid counter for waiting RequestHandler instances

## Features

* Add ttl with a default value of 10 seconds to RequestHandler

# Version 0.7.5

## Bugfixes

* None

## Features

* Let RequestHandlerManager handle RequestHandler instances dynamically
* Throw RequestHandlerTimeoutException when we can't handle request within defined timeout

# Version 0.7.4

## Bugfixes

* None

## Features

* Add shutdown functionality for request handlers
* Handle a maximum of 50 request before shutdown request handler
* Integrate RequestHandlerManager class to asynchronously restart request handlers that has been shutdown

# Version 0.7.3

## Bugfixes

* Resolve PHPMD warnings and errors
* Bugfix for endless loop in ServletEngine::process() with concurrent requests

## Features

* None

# Version 0.7.2

## Bugfixes

* None

## Features

* Refactoring ANT PHPUnit execution process
* Composer integration by optimizing folder structure (move bootstrap.php + phpunit.xml.dist => phpunit.xml)
* Switch to new appserver-io/build build- and deployment environment

# Version 0.7.1

## Bugfixes

* None

## Features

* Add CHANGELOG.md
* Set composer dependency for techdivision/appserver to >=0.8