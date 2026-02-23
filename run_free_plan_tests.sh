#!/bin/bash

echo "========================================"
echo "Running Free Plan Logic Tests"
echo "========================================"
echo ""

cd "$(dirname "$0")"

php artisan test --filter=ListingCreationWithFreePlanTest

echo ""
echo "========================================"
echo "Tests Completed"
echo "========================================"
