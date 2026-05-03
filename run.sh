#!/bin/bash
PORT=${PORT:-3000}
echo "Starting PHP server → http://localhost:$PORT"
echo "Press Ctrl+C to stop."
php -S localhost:$PORT -t "$(dirname "$0")"
