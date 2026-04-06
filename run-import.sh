#!/bin/bash
# Run from the silvester-shop directory:
#   chmod +x run-import.sh && ./run-import.sh

TOTAL=184
WP_PATH="$(pwd)"

echo "Starting product import (1 product per run)..."

for i in $(seq 1 $TOTAL); do
    echo "--- Run $i/$TOTAL ---"
    wp eval-file import-products.php --path="$WP_PATH"

    # Stop if progress file says we're done
    if [ -f import-progress.txt ]; then
        PROGRESS=$(cat import-progress.txt)
        if [ "$PROGRESS" -ge "$TOTAL" ]; then
            echo "Import complete!"
            break
        fi
    fi

    # Small pause between requests to avoid overloading
    sleep 1
done

echo "Done."
