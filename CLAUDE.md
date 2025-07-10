# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This project creates PHP FFI (Foreign Function Interface) bindings for llhttp - a high-performance HTTP parser written in C. The goal is to provide an object-oriented, event-driven PHP wrapper that makes it easy to parse HTTP requests and responses.

### Core Architecture

The project implements an event-driven parser with the following structure:

- **Llhttp\Ffi\Parser**: Main parser class that wraps the llhttp C library via PHP FFI
- **Event System**: Callback-based architecture for different parsing stages:
  - `messageBegin`: Parsing starts
  - `header`: Each header line parsed
  - `headersComplete`: All headers parsed
  - `body`: Body chunks received
  - `messageComplete`: Parsing complete
- **State Management**: `pause()`/`resume()` methods for controlling parser flow
- **Error Handling**: `Llhttp\Exception` for parser errors
- **PSR-7 Integration**: Designed to work seamlessly with PSR-7 request/response objects

### Parser Types

- `Parser::TYPE_REQUEST`: For parsing HTTP requests
- `Parser::TYPE_RESPONSE`: For parsing HTTP responses

### Expected Project Structure

```
src/
├── Ffi/
│   └── Parser.php       # Main FFI parser wrapper
├── Parser.php           # Parser constants and base functionality
└── Exception.php        # Custom exception class
tests/                   # PHPUnit test suite
vendor/                  # Composer dependencies
composer.json            # Package configuration
```

## Development Commands

### Package Management
```bash
composer install        # Install dependencies
composer dump-autoload  # Regenerate autoloader
```

### Testing
```bash
composer test           # Run PHPUnit tests
php vendor/bin/phpunit  # Direct PHPUnit execution
```

### Code Quality
```bash
composer lint           # Run PHP CodeSniffer
composer stan           # Run PHPStan static analysis
```

## Commit Rules

This project follows [Conventional Commits](https://www.conventionalcommits.org/) specification:

### Format
```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Types
- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, missing semicolons, etc.)
- **refactor**: Code changes that neither fix bugs nor add features
- **perf**: Performance improvements
- **test**: Adding or modifying tests
- **chore**: Changes to build process, dependencies, or auxiliary tools

### Examples
```
feat(parser): add HTTP request parsing support
fix(ffi): handle null pointer exceptions in parser
docs: update README with usage examples
test(parser): add unit tests for header parsing
chore: update composer dependencies
```

### Breaking Changes
Mark breaking changes with `!` after the type/scope:
```
feat!: change parser API to use events instead of callbacks
```

## FFI Integration Notes

- The project uses PHP's FFI extension to interface with the llhttp C library
- Ensure FFI extension is enabled in PHP configuration
- The llhttp library must be compiled and available as a shared library
- Handle memory management carefully when working with C structures