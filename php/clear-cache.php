<?php
/**
 * Clear Calendar Cache
 * Clears cached calendar data to force fresh data fetch
 */

require_once 'config.php';

$properties = ['stone', 'copper', 'cedar'];

echo "Clearing calendar cache...\n\n";

foreach ($properties as $propertyId) {
    $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
    
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        echo "✅ Cleared cache for $propertyId\n";
    } else {
        echo "⚠️  No cache file for $propertyId\n";
    }
}

echo "\nDone! Cache cleared for all properties.\n";
echo "Calendar will fetch fresh data on next load.\n";
