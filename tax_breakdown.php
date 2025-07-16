<?php
/**
 * KRA Tax Integration Framework
 * Integrates with KRA's GAVA Connect API for real-time tax calculations
 */

class KRATaxIntegration {
    private $api_base_url;
    private $api_key;
    private $api_secret;
    private $conn;
    
    public function __construct($conn, $api_key = null, $api_secret = null) {
        $this->conn = $conn;
        $this->api_base_url = 'https://sbx.kra.go.ke/oauth/v1/generate?grant_type=client_credentials'; // KRA API endpoint
        $this->api_key = $api_key ?: $_ENV['KRA_API_KEY'];
        $this->api_secret = $api_secret ?: $_ENV['KRA_API_SECRET'];
    }
    
    /**
     * Get access token for KRA API authentication
     */
    private function getAccessToken() {
        $cache_key = 'c9SQxWWhmdVRlyh0zh8gZDTkubVF';
        
        // Check if token exists in session and is still valid
        if (isset($_SESSION[$cache_key]) && $_SESSION['kra_token_expires'] > time()) {
            return $_SESSION[$cache_key];
        }
        
        $auth_url = $this->api_base_url . 'auth/token';
        $auth_data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->api_key,
            'client_secret' => $this->api_secret
        ];
        
        $response = $this->makeAPICall($auth_url, $auth_data, 'POST', false);
        
        if ($response && isset($response['access_token'])) {
            $_SESSION[$cache_key] = $response['access_token'];
            $_SESSION['kra_token_expires'] = time() + ($response['expires_in'] - 300); // 5 min buffer
            return $response['access_token'];
        }
        
        return false;
    }
    
    /**
     * Calculate rental income tax using KRA API
     */
    public function calculateRentalTax($annual_rental_income, $property_details = []) {
        $token = $this->getAccessToken();
        if (!$token) {
            return $this->getFallbackTaxRate($annual_rental_income);
        }
        
        $tax_url = $this->api_base_url . 'tax/rental-income/calculate';
        $tax_data = [
            'annual_income' => $annual_rental_income,
            'property_type' => $property_details['type'] ?? 'residential',
            'location' => $property_details['location'] ?? '',
            'year' => date('Y')
        ];
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeAPICall($tax_url, $tax_data, 'POST', true, $headers);
        
        if ($response && isset($response['tax_rate'])) {
            // Cache the response for future use
            $this->cacheTaxCalculation($annual_rental_income, $response);
            return [
                'tax_rate' => $response['tax_rate'],
                'tax_amount' => $response['tax_amount'],
                'source' => 'KRA_API',
                'calculation_date' => date('Y-m-d H:i:s')
            ];
        }
        
        // Fallback to manual calculation if API fails
        return $this->getFallbackTaxRate($annual_rental_income);
    }
    
    /**
     * Fallback tax calculation (your current logic)
     */
    private function getFallbackTaxRate($annual_income) {
        // Current KRA rental income tax rates (as of 2024/2025)
        if ($annual_income <= 288000) {
            $rate = 0; // Tax-free threshold
        } elseif ($annual_income <= 15000000) {
            $rate = 7.5; // Your current rate
        } else {
            $rate = 10; // Higher rate for income above 15M
        }
        
        return [
            'tax_rate' => $rate,
            'tax_amount' => ($annual_income * $rate) / 100,
            'source' => 'FALLBACK',
            'calculation_date' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Cache tax calculations to reduce API calls
     */
    private function cacheTaxCalculation($income, $response) {
        $cache_query = "INSERT INTO tax_cache (income_amount, tax_rate, tax_amount, cached_at) 
                       VALUES (?, ?, ?, NOW()) 
                       ON DUPLICATE KEY UPDATE 
                       tax_rate = VALUES(tax_rate), 
                       tax_amount = VALUES(tax_amount), 
                       cached_at = NOW()";
        
        $stmt = $this->conn->prepare($cache_query);
        if ($stmt) {
            $stmt->bind_param('ddd', $income, $response['tax_rate'], $response['tax_amount']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Get cached tax calculation
     */
    public function getCachedTaxRate($income) {
        $cache_query = "SELECT tax_rate, tax_amount, cached_at 
                       FROM tax_cache 
                       WHERE income_amount = ? 
                       AND cached_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $stmt = $this->conn->prepare($cache_query);
        if ($stmt) {
            $stmt->bind_param('d', $income);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return [
                    'tax_rate' => $row['tax_rate'],
                    'tax_amount' => $row['tax_amount'],
                    'source' => 'CACHE',
                    'calculation_date' => $row['cached_at']
                ];
            }
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Make API calls with error handling
     */
    private function makeAPICall($url, $data, $method = 'POST', $auth_required = true, $additional_headers = []) {
        $ch = curl_init();
        
        $headers = array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $additional_headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("KRA API Error: " . $error);
            return false;
        }
        
        if ($http_code !== 200) {
            error_log("KRA API HTTP Error: " . $http_code . " - " . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Calculate tax for individual payment (monthly)
     */
    public function calculateMonthlyTax($monthly_amount, $tenant_id = null) {
        // Get annual projection
        $annual_projection = $monthly_amount * 12;
        
        // Get tax calculation
        $tax_calc = $this->getCachedTaxRate($annual_projection);
        if (!$tax_calc) {
            $tax_calc = $this->calculateRentalTax($annual_projection);
        }
        
        // Return monthly portion
        return [
            'monthly_tax_rate' => $tax_calc['tax_rate'],
            'monthly_tax_amount' => ($monthly_amount * $tax_calc['tax_rate']) / 100,
            'annual_projection' => $annual_projection,
            'source' => $tax_calc['source']
        ];
    }
}

/**
 * Updated tax breakdown page with KRA integration
 */
session_start();
include('includes/db.php');
require_once 'config.php';

// Initialize KRA Tax Integration
$kra_tax = new KRATaxIntegration($conn);
$landlord_id = $_SESSION['user_id'];
ob_start();
?>

<style>
table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    margin-top: 20px;
    background-color: white;
}
thead {
    background-color: #007bff;
    color: white;
}
th, td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: center;
}
tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}
tbody tr:hover {
    background-color: #f1f1f1;
}
td {
    color: #333;
}
.error {
    color: red;
    text-align: center;
}
.empty {
    text-align: center;
    color: #555;
}
.kra-badge {
    background: #28a745;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}
.fallback-badge {
    background: #ffc107;
    color: black;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}
.cache-badge {
    background: #17a2b8;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}
</style>

<h2>📊 Tax Breakdown (KRA Integrated)</h2>
<p>This section provides a breakdown of taxes calculated using KRA's real-time tax calculation system.</p>

<table>
<thead>
<tr>
    <th>#</th>
    <th>Tenant</th>
    <th>Room</th>
    <th>Amount Paid (Ksh)</th>
    <th>Tax Rate (%)</th>
    <th>Tax Amount (Ksh)</th>
    <th>Source</th>
    <th>Date Paid</th>
</tr>
</thead>
<tbody>
<?php
$query = "SELECT
    t.name AS tenant,
    r.room_number AS room,
    p.amount,
    p.created_at,
    p.tenant_id
    FROM payments p
    JOIN tenants t ON p.tenant_id = t.tenant_id
    JOIN rooms r ON p.room_number = r.room_number
    WHERE r.landlord_id = '$landlord_id'
    ORDER BY p.created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "<tr><td colspan='8' class='error'>Query error: " . mysqli_error($conn) . "</td></tr>";
} elseif (mysqli_num_rows($result) > 0) {
    $count = 1;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $amount = $row['amount'];
        
        // Get KRA tax calculation
        $tax_data = $kra_tax->calculateMonthlyTax($amount, $row['tenant_id']);
        
        $tax_rate = $tax_data['monthly_tax_rate'];
        $tax_amount = $tax_data['monthly_tax_amount'];
        $source = $tax_data['source'];
        
        // Badge styling based on source
        $badge_class = '';
        $badge_text = '';
        switch($source) {
            case 'KRA_API':
                $badge_class = 'kra-badge';
                $badge_text = 'KRA Live';
                break;
            case 'CACHE':
                $badge_class = 'cache-badge';
                $badge_text = 'KRA Cached';
                break;
            default:
                $badge_class = 'fallback-badge';
                $badge_text = 'Manual';
        }
?>
<tr>
    <td><?= $count++ ?></td>
    <td><?= htmlspecialchars($row['tenant']) ?></td>
    <td><?= htmlspecialchars($row['room']) ?></td>
    <td><?= number_format($amount, 2) ?></td>
    <td><?= number_format($tax_rate, 2) ?>%</td>
    <td><?= number_format($tax_amount, 2) ?></td>
    <td><span class="<?= $badge_class ?>"><?= $badge_text ?></span></td>
    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
</tr>
<?php
    }
} else {
    echo "<tr><td colspan='8' class='empty'>No tax data available.</td></tr>";
}
?>
</tbody>
</table>

<?php
$content = ob_get_clean();
include 'layout.php';
?>

