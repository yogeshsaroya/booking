<?php
/**
 * Debug Script - Check Database Bookings
 * Access via: http://localhost/booking/php/debug-availability.php?property=stone
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

$propertyId = $_GET['property'] ?? null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Calendar Availability</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9f9f9; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .property-select { margin: 20px 0; }
        select { padding: 8px; font-size: 14px; }
        button { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Calendar Availability Debug Tool</h1>
        
        <div class="property-select">
            <label>Select Property:</label>
            <select onchange="location.href='?property=' + this.value">
                <option value="">-- Choose Property --</option>
                <option value="stone" <?php echo $propertyId === 'stone' ? 'selected' : ''; ?>>Stone</option>
                <option value="copper" <?php echo $propertyId === 'copper' ? 'selected' : ''; ?>>Copper</option>
                <option value="cedar" <?php echo $propertyId === 'cedar' ? 'selected' : ''; ?>>Cedar</option>
            </select>
            <?php if ($propertyId): ?>
                <button onclick="clearCache()">🗑️ Clear Cache</button>
            <?php endif; ?>
        </div>

        <?php if (!$propertyId): ?>
            <div class="section">
                <p>Please select a property to debug.</p>
            </div>
        <?php else: ?>
            <!-- Database Bookings Section -->
            <div class="section">
                <h2>📊 Database Bookings for <?php echo ucfirst($propertyId); ?></h2>
                
                <?php
                try {
                    $pdo = getDBConnection();
                    if (!$pdo) {
                        echo '<p class="error">❌ Database connection failed</p>';
                    } else {
                        echo '<p class="success">✅ Database connection OK</p>';
                        
                        // Get all bookings for this property
                        $stmt = $pdo->prepare("
                            SELECT booking_id, check_in, check_out, status, created_at 
                            FROM bookings 
                            WHERE property = :property 
                            ORDER BY check_in DESC
                        ");
                        $stmt->execute(['property' => $propertyId]);
                        $allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo '<h3>All Bookings (' . count($allBookings) . ')</h3>';
                        
                        if (count($allBookings) > 0) {
                            echo '<table>';
                            echo '<tr><th>Booking ID</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Blocks Calendar?</th><th>Created</th></tr>';
                            
                            $blockingStatuses = ['pending', 'pending_bitcoin', 'confirmed', 'paid'];
                            $today = date('Y-m-d');
                            
                            foreach ($allBookings as $booking) {
                                $blocksCalendar = (in_array($booking['status'], $blockingStatuses) && $booking['check_out'] >= $today) ? '✅ Yes' : '❌ No';
                                $statusColor = in_array($booking['status'], $blockingStatuses) ? 'success' : 'warning';
                                
                                echo '<tr>';
                                echo '<td><code>' . htmlspecialchars($booking['booking_id']) . '</code></td>';
                                echo '<td>' . $booking['check_in'] . '</td>';
                                echo '<td>' . $booking['check_out'] . '</td>';
                                echo '<td class="' . $statusColor . '">' . ucfirst($booking['status']) . '</td>';
                                echo '<td>' . $blocksCalendar . '</td>';
                                echo '<td>' . date('M d, Y H:i', strtotime($booking['created_at'])) . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                        } else {
                            echo '<p class="warning">⚠️ No bookings found in database for ' . ucfirst($propertyId) . '</p>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>

            <!-- Calendar Blocked Dates Section -->
            <div class="section">
                <h2>📅 Calendar Blocked Dates</h2>
                
                <?php
                try {
                    // Build correct URL - use relative path from current directory
                    $url = "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/calendar-sync.php?property={$propertyId}";
                    
                    echo '<p>Testing URL: <code>' . htmlspecialchars($url) . '</code></p>';
                    
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    if ($curlError) {
                        echo '<p class="error">❌ cURL Error: ' . htmlspecialchars($curlError) . '</p>';
                    } else if ($httpCode === 200) {
                        $data = json_decode($response, true);
                        
                        if ($data['success']) {
                            $blockedDates = $data['blockedDates'] ?? [];
                            
                            echo '<p class="success">✅ Calendar sync successful</p>';
                            echo '<p>Last updated: <code>' . $data['lastUpdated'] . '</code></p>';
                            echo '<p>Total blocked dates: <strong>' . count($blockedDates) . '</strong></p>';
                            
                            if (count($blockedDates) > 0) {
                                echo '<h3>Blocked Dates (first 30):</h3>';
                                echo '<pre>' . implode("\n", array_slice($blockedDates, 0, 30)) . '</pre>';
                                if (count($blockedDates) > 30) {
                                    echo '<p><em>... and ' . (count($blockedDates) - 30) . ' more dates</em></p>';
                                }
                            } else {
                                echo '<p class="warning">⚠️ No blocked dates found</p>';
                            }
                        } else {
                            echo '<p class="error">❌ Calendar sync failed: ' . htmlspecialchars($data['error'] ?? 'Unknown error') . '</p>';
                        }
                    } else {
                        echo '<p class="error">❌ HTTP Error ' . $httpCode . '</p>';
                        echo '<p>Response: <code>' . htmlspecialchars(substr($response, 0, 200)) . '</code></p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>

            <!-- Cache Status Section -->
            <div class="section">
                <h2>💾 Cache Status</h2>
                
                <?php
                $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
                
                if (file_exists($cacheFile)) {
                    $cacheAge = time() - filemtime($cacheFile);
                    $cacheMinutes = round($cacheAge / 60);
                    $cacheData = json_decode(file_get_contents($cacheFile), true);
                    
                    echo '<p class="success">✅ Cache file exists</p>';
                    echo '<p>Location: <code>' . htmlspecialchars($cacheFile) . '</code></p>';
                    echo '<p>Cache age: <strong>' . $cacheMinutes . ' minutes</strong> ago</p>';
                    echo '<p>Cached dates: <strong>' . count($cacheData['blockedDates'] ?? []) . '</strong></p>';
                    echo '<p>Cache timestamp: <code>' . date('Y-m-d H:i:s', $cacheData['timestamp'] ?? 0) . '</code></p>';
                } else {
                    echo '<p class="warning">⚠️ No cache file found</p>';
                    echo '<p>Cache will be created on next calendar load</p>';
                }
                ?>
            </div>

            <!-- Test Availability Section -->
            <div class="section">
                <h2>🔬 Test Availability Check</h2>
                
                <?php
                $testCheckIn = $_GET['test_check_in'] ?? date('Y-m-d', strtotime('+2 days'));
                $testCheckOut = $_GET['test_check_out'] ?? date('Y-m-d', strtotime('+5 days'));
                ?>
                
                <form method="get">
                    <input type="hidden" name="property" value="<?php echo htmlspecialchars($propertyId); ?>">
                    <label>Check-in: <input type="date" name="test_check_in" value="<?php echo $testCheckIn; ?>"></label>
                    <label>Check-out: <input type="date" name="test_check_out" value="<?php echo $testCheckOut; ?>"></label>
                    <button type="submit">Test Availability</button>
                </form>
                
                <?php
                if (isset($_GET['test_check_in']) && isset($_GET['test_check_out'])) {
                    try {
                        $checker = new \stdClass();
                        $pdo = getDBConnection();
                        
                        // Check database
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count
                            FROM bookings
                            WHERE property = :property
                            AND status IN ('pending', 'pending_bitcoin', 'confirmed', 'paid')
                            AND (
                                (check_in <= :check_in AND check_out > :check_in)
                                OR (check_in < :check_out AND check_out >= :check_out)
                                OR (check_in >= :check_in AND check_out <= :check_out)
                            )
                        ");
                        
                        $stmt->execute([
                            'property' => $propertyId,
                            'check_in' => $testCheckIn,
                            'check_out' => $testCheckOut
                        ]);
                        
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $hasConflict = $result['count'] > 0;
                        
                        echo '<h3>Results for ' . htmlspecialchars($testCheckIn) . ' to ' . htmlspecialchars($testCheckOut) . ':</h3>';
                        
                        if ($hasConflict) {
                            echo '<p class="error">❌ BLOCKED - Conflict found in database</p>';
                        } else {
                            echo '<p class="success">✅ AVAILABLE - No conflicts in database</p>';
                        }
                        
                    } catch (Exception $e) {
                        echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                }
                ?>
            </div>

            <!-- Instructions -->
            <div class="section">
                <h2>🛠️ Troubleshooting</h2>
                <h3>If dates are not showing as blocked:</h3>
                <ol>
                    <li><strong>Check Database:</strong> Verify booking exists in the table above with status "pending", "confirmed", or "paid"</li>
                    <li><strong>Check Property Name:</strong> Make sure booking property matches exactly (case-sensitive): <code><?php echo htmlspecialchars($propertyId); ?></code></li>
                    <li><strong>Clear Cache:</strong> Click "Clear Cache" button above to force fresh data fetch</li>
                    <li><strong>Reload Calendar:</strong> Hard refresh (Ctrl+F5) the property page to clear browser cache</li>
                    <li><strong>Check Dates:</strong> Booking check_out date must be >= today's date to be blocked</li>
                </ol>
                
                <h3>Database bookings status:</h3>
                <ul>
                    <li><strong>✅ Blocks calendar:</strong> pending, pending_bitcoin, confirmed, paid</li>
                    <li><strong>❌ Doesn't block:</strong> failed, cancelled, expired</li>
                </ul>
            </div>

        <?php endif; ?>
    </div>

    <script>
    function clearCache() {
        if (confirm('Clear cache for <?php echo htmlspecialchars($propertyId); ?>?')) {
            window.location.href = '?property=<?php echo htmlspecialchars($propertyId); ?>&clear_cache=1';
        }
    }
    </script>
</body>
</html>
