# Optimal Page Counter

A lightweight, cache-first WordPress page counter focused on performance, privacy, and minimal database impact.

## Status
- v0.1.0: Minimal plugin loads (skeleton)

## Goals
- Cache-compatible counting via REST + small JS ping
- Minimal database writes via batching
- Role exclusion + IP exclusion (no raw IP storage)
- Simple reset tools and admin QoL

## Development
This project is in active development. Next milestone: REST hit endpoint + JS ping.