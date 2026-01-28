<?php
/**
 * SmartStayz Admin Panel - Bookings Dashboard
 * No authentication (for development only)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartStayz Admin - Bookings Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }

        .stat-card.confirmed { border-left-color: #10b981; }
        .stat-card.pending { border-left-color: #f59e0b; }
        .stat-card.failed { border-left-color: #ef4444; }
        .stat-card.total { border-left-color: #667eea; }

        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.625rem 1.25rem;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #f9fafb;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .bookings-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.9rem;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-canceled {
            background: #e5e7eb;
            color: #374151;
        }

        .booking-id {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #667eea;
            font-weight: 600;
        }

        .property-name {
            font-weight: 600;
            color: #111827;
        }

        .guest-info {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .amount {
            font-weight: 700;
            color: #059669;
            font-size: 1rem;
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .loading::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        .no-bookings {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .refresh-btn {
            padding: 0.625rem 1.25rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background: #5568d3;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .admin-header {
                padding: 1rem;
            }

            .admin-header h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            table {
                font-size: 0.8rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>📊 SmartStayz Admin Dashboard</h1>
        <p>Manage all property bookings and reservations</p>
    </div>

    <div class="container">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Bookings</h3>
                <div class="value" id="stat-total">-</div>
            </div>
            <div class="stat-card confirmed">
                <h3>Confirmed</h3>
                <div class="value" id="stat-confirmed">-</div>
            </div>
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="value" id="stat-pending">-</div>
            </div>
            <div class="stat-card failed">
                <h3>Failed</h3>
                <div class="value" id="stat-failed">-</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search by booking ID, name, email...">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-status="all">All</button>
                <button class="filter-btn" data-status="confirmed">Confirmed</button>
                <button class="filter-btn" data-status="pending">Pending</button>
                <button class="filter-btn" data-status="failed">Failed</button>
                <button class="refresh-btn" onclick="loadBookings()">↻ Refresh</button>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="bookings-table">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Property</th>
                            <th>Guest</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Guests</th>
                            <th>Special Requests</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTableBody">
                        <tr>
                            <td colspan="11" class="loading">Loading bookings</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let allBookings = [];
        let currentFilter = 'all';

        // Load bookings on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadBookings();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', filterBookings);
            
            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.status;
                    filterBookings();
                });
            });
        });

        async function loadBookings() {
            try {
                const response = await fetch('get-bookings.php');
                const data = await response.json();
                
                if (data.success) {
                    allBookings = data.bookings;
                    updateStats(allBookings);
                    filterBookings();
                } else {
                    showError('Failed to load bookings');
                }
            } catch (error) {
                showError('Error loading bookings: ' + error.message);
            }
        }

        function updateStats(bookings) {
            const stats = {
                total: bookings.length,
                confirmed: bookings.filter(b => b.status === 'confirmed').length,
                pending: bookings.filter(b => b.status === 'pending' || b.status.includes('pending')).length,
                failed: bookings.filter(b => b.status === 'failed' || b.status === 'canceled').length
            };

            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-confirmed').textContent = stats.confirmed;
            document.getElementById('stat-pending').textContent = stats.pending;
            document.getElementById('stat-failed').textContent = stats.failed;
        }

        function filterBookings() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            let filtered = allBookings.filter(booking => {
                // Status filter
                if (currentFilter !== 'all') {
                    if (currentFilter === 'pending') {
                        if (!booking.status.includes('pending')) return false;
                    } else if (booking.status !== currentFilter) {
                        return false;
                    }
                }
                
                // Search filter
                if (searchTerm) {
                    const searchable = `
                        ${booking.booking_id}
                        ${booking.first_name} ${booking.last_name}
                        ${booking.email}
                        ${booking.property}
                    `.toLowerCase();
                    
                    if (!searchable.includes(searchTerm)) return false;
                }
                
                return true;
            });

            displayBookings(filtered);
        }

        function displayBookings(bookings) {
            const tbody = document.getElementById('bookingsTableBody');
            
            if (bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="no-bookings">No bookings found</td></tr>';
                return;
            }

            tbody.innerHTML = bookings.map(booking => `
                <tr>
                    <td><span class="booking-id">${booking.booking_id}</span></td>
                    <td><span class="property-name">${formatPropertyName(booking.property)}</span></td>
                    <td>
                        <div>${booking.first_name} ${booking.last_name}</div>
                        <div class="guest-info">${booking.email}</div>
                        <div class="guest-info">${booking.phone}</div>
                    </td>
                    <td>${formatDate(booking.check_in)}</td>
                    <td>${formatDate(booking.check_out)}</td>
                    <td>${booking.guests}${booking.has_pets == 1 ? ' 🐾' : ''}</td>
                    <td><div style="max-width: 200px; font-size: 0.85rem; color: #6b7280;">${escapeHtml(booking.special_requests) || '-'}</div></td>
                    <td><span class="amount">$${parseFloat(booking.amount).toFixed(2)}</span></td>
                    <td>${formatPaymentMethod(booking.payment_method)}</td>
                    <td>${formatStatus(booking.status)}</td>
                    <td>${formatDateTime(booking.created_at)}</td>
                </tr>
            `).join('');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatPropertyName(property) {
            const names = {
                'cedar': 'Cedar Retreat',
                'copper': 'Copper Lodge',
                'stone': 'Stone Haven'
            };
            return names[property] || property;
        }

        function formatPaymentMethod(method) {
            const methods = {
                'stripe': 'Credit Card',
                'bitcoin': 'Bitcoin',
                'venmo': 'Venmo',
                'cashapp': 'Cash App'
            };
            return methods[method] || method;
        }

        function formatStatus(status) {
            const statusClass = status.includes('pending') ? 'pending' : 
                               status === 'confirmed' ? 'confirmed' : 
                               status === 'failed' || status === 'canceled' ? 'failed' : 'pending';
            
            return `<span class="status-badge status-${statusClass}">${status}</span>`;
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showError(message) {
            document.getElementById('bookingsTableBody').innerHTML = 
                `<tr><td colspan="11" style="color: #ef4444; text-align: center; padding: 2rem;">${message}</td></tr>`;
        }
    </script>
</body>
</html>
