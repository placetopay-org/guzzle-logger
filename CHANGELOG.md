# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [2.0.4] - 2025-10-27

### Fixed
- Resolved a stream handling issue in the logger middleware that caused empty response bodies.

### Changed
- Improved body formatting logic to correctly handle non-JSON content in both requests and responses

## [2.0.3] - 2025-05-29

### Changed
- Removed deprecated explicit nullable parameter in `HttpLog::log
- Updated log message in `HttpLog::formatBody` from 'Failed empty response body' to 'Failed empty body'

## [2.0.2] - 2025-05-23

### Changed
- Improved error log message in `HttpLog::formatBody` when JSON response decoding fails.

## [2.0.1] - 2025-02-20

### Changed
- Sanitizer correctly replaces characters with * based on the length of the CardNumber.

## [2.0.0] - 2024-11-19

### Changed
- Upgrades runtime to php 8.3

## [1.0.1] - 2024-03-26

### Changed
- Adds the `url` field to the response log record.

## [1.0.0] - 2023-11-23

### Changed
- Upgrades runtime to php 8.2.

## [0.1.1] - 2023-07-10

### Changed
- Improve  argument against the context of log records.

## [0.1.0] - 2023-06-27
### Added
- Created the first functionality of the middleware