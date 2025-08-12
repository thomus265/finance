<?php
require_once '../controller/main.php';


$page = $_POST['page'] ?? 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$sort_by = $_POST['sort_by'] ?? 'id';
$sort_dir = $_POST['sort_dir'] ?? 'asc';
$global = $_POST['global'] ?? '';
$filters = $_POST['filters'] ?? [];

$where = "WHERE 1";
$params = [];

if (!empty($global)) {
    $where .= "AND (title LIKE ? OR department LIKE ? OR state LIKE ? OR user_id LIKE ? OR tr_date LIKE ?)";
    for ($i = 0; $i < 5; $i++) $params[] = "%$global%";
}

foreach ($filters as $col => $val) {
    if ($val !== '') {
        $where .= " AND $col LIKE ?";
        $params[] = "%$val%";
    }
}

$db = new Database();
$total = $db->query("SELECT COUNT(*) as total FROM transactions t INNER JOIN users u $where", ...$params)->fetchArray()['total'];
$totalPages = ceil($total / $limit);

$sql = "SELECT * FROM transactions $where ORDER BY $sort_by $sort_dir LIMIT $limit OFFSET $offset";
$rows = $db->query($sql, ...$params)->fetchAll();

$html = '';
foreach ($rows as $r) {
    $html .= "<tr>
        <td>{$r['id']}</td>
        <td>{$r['title']}</td>
        <td>{$r['amount']}</td>
        <td>" . 
            ($r['department'] === '1' ? 'Operations' : 
                ($r['department'] === '2' ? 'Programs' : 
                    ($r['department'] === '3' ? 'other' : $r['department']))
            ) 
        . "</td>
        <td>" . 
            ($r['state'] === '1' ? 'Submitted' : 
                ($r['state'] === '2' ? 'In progress' : 
                    ($r['state'] === '3' ? 'Paid' : 
                        ($r['state'] === '4' ? 'Sent Back' : $r['state'])
                    )
                )
            )
        . "</td>
        <td>{$r['user_name']}</td>
        <td>{$r['tr_date']}</td>
    </tr>";
}

// pagination
$pagi = '';
for ($i = 1; $i <= $totalPages; $i++) {
    $active = $i == $page ? "style='font-weight:bold'" : "";
    $pagi .= "<button class='page-btn' data-page='$i' $active>$i</button>";
}

echo json_encode([
    'table' => $html,
    'pagination' => $pagi
]);
