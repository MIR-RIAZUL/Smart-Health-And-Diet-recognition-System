<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Handle Add / Edit / Delete Food actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf)) {
        setFlashMessage('error', 'Security token mismatch. Please try again.');
        header('Location: foods.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_food' || $action === 'edit_food') {
        $foodName = trim($_POST['food_name'] ?? '');
        $servingSize = trim($_POST['serving_size'] ?? '100g');
        $calories = (float)($_POST['calories'] ?? 0);
        $protein = (float)($_POST['protein'] ?? 0);
        $carbs = (float)($_POST['carbohydrates'] ?? 0);
        $fat = (float)($_POST['fat'] ?? 0);
        $fiber = (float)($_POST['fiber'] ?? 0);

        if (empty($foodName)) {
            setFlashMessage('error', 'Food name is required.');
        } else {
            if ($action === 'add_food') {
                $stmt = $pdo->prepare("
                    INSERT INTO food_items (food_name, serving_size, calories, protein, carbohydrates, fat, fiber)
                    VALUES (:name, :serving, :cal, :protein, :carbs, :fat, :fiber)
                ");
                $stmt->execute([
                    'name'    => $foodName,
                    'serving' => $servingSize,
                    'cal'     => $calories,
                    'protein' => $protein,
                    'carbs'   => $carbs,
                    'fat'     => $fat,
                    'fiber'   => $fiber
                ]);
                setFlashMessage('success', "Food item '{$foodName}' was added to the database.");
            } else {
                $foodId = (int)($_POST['food_id'] ?? 0);
                $stmt = $pdo->prepare("
                    UPDATE food_items 
                    SET food_name = :name, serving_size = :serving, calories = :cal, 
                        protein = :protein, carbohydrates = :carbs, fat = :fat, fiber = :fiber
                    WHERE id = :id
                ");
                $stmt->execute([
                    'name'    => $foodName,
                    'serving' => $servingSize,
                    'cal'     => $calories,
                    'protein' => $protein,
                    'carbs'   => $carbs,
                    'fat'     => $fat,
                    'fiber'   => $fiber,
                    'id'      => $foodId
                ]);
                setFlashMessage('success', "Food item '{$foodName}' was successfully updated.");
            }
        }
    } elseif ($action === 'delete_food') {
        $foodId = (int)($_POST['food_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = :id");
        $stmt->execute(['id' => $foodId]);
        setFlashMessage('success', 'Food item was removed from database.');
    }

    header('Location: foods.php');
    exit;
}

// Search parameter
$search = trim($_GET['search'] ?? '');
$query = "SELECT * FROM food_items WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND food_name LIKE :search";
    $params['search'] = "%{$search}%";
}

$query .= " ORDER BY food_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$foodList = $stmt->fetchAll();

$totalFoods = count($foodList);
$currentPage = 'foods';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Track - Food Database Management</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .food-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .food-modal.active { display: flex; }
    </style>
</head>
<body class="admin-full-page">
    <div class="dashboard-layout">
        
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="main-content" style="overflow-y: auto;">
            <div class="admin-page-container" style="max-width: 1300px; padding: 30px 40px;">
                
                <?php renderFlashMessage(); ?>

                <!-- Controls Bar -->
                <div class="admin-controls-bar">
                    <div class="controls-left">
                        <div class="title-icon icon-orange-outline">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="section-title">Food & Nutritional Database</h2>
                            <p class="section-subtitle"><?= $totalFoods ?> items recorded</p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="foods.php" class="search-box">
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search food by name (e.g. Rice, Chicken)...">
                    </form>

                    <!-- Add Food Button -->
                    <button type="button" class="primary-btn" onclick="openAddFoodModal()" style="width: auto; padding: 0 20px; height: 44px; margin-bottom: 0; display: flex; align-items: center; gap: 8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Food Item
                    </button>
                </div>

                <!-- Food Table Card -->
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>FOOD ITEM</th>
                                <th>SERVING</th>
                                <th class="text-right">CALORIES</th>
                                <th class="text-right">PROTEIN (g)</th>
                                <th class="text-right">CARBS (g)</th>
                                <th class="text-right">FAT (g)</th>
                                <th class="text-right">FIBER (g)</th>
                                <th class="text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($foodList)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No foods found. Click <strong>Add Food Item</strong> to insert into the catalog.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($foodList as $f): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text-light); font-size: 15px;"><?= e($f->food_name ?? $f['food_name']) ?></strong>
                                        </td>
                                        <td class="text-muted" style="font-size: 13px;">
                                            <?= e($f['serving_size']) ?>
                                        </td>
                                        <td class="text-right font-bold" style="color: #2ecc71;">
                                            <?= number_format($f['calories'], 1) ?> kcal
                                        </td>
                                        <td class="text-right" style="color: #3498db; font-weight: 500;">
                                            <?= number_format($f['protein'], 1) ?>
                                        </td>
                                        <td class="text-right" style="color: #e67e22; font-weight: 500;">
                                            <?= number_format($f['carbohydrates'], 1) ?>
                                        </td>
                                        <td class="text-right" style="color: #9b59b6; font-weight: 500;">
                                            <?= number_format($f['fat'], 1) ?>
                                        </td>
                                        <td class="text-right" style="color: #1aae8d; font-weight: 500;">
                                            <?= number_format($f['fiber'], 1) ?>
                                        </td>
                                        <td class="text-right">
                                            <div style="display: inline-flex; gap: 8px;">
                                                
                                                <!-- Edit Food Modal Trigger -->
                                                <button type="button" class="action-btn view-btn" title="Edit Nutrition Facts" 
                                                        onclick="openEditFoodModal(<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                                </button>

                                                <!-- Delete Food Form -->
                                                <form method="POST" action="foods.php" style="display: inline;" 
                                                      onsubmit="return confirm('Delete <?= e($f['food_name']) ?> from database?');">
                                                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="delete_food">
                                                    <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
                                                    <button type="submit" class="action-btn reject-btn" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;" title="Delete Food">
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <!-- Add / Edit Food Modal -->
    <div id="foodModal" class="food-modal">
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-top">
                <div class="modal-titles">
                    <h2 id="modalFoodTitle">Add Food Item</h2>
                    <p>Nutrition Facts per Standard Serving</p>
                </div>
                <button type="button" class="close-btn" onclick="closeFoodModal()">&times;</button>
            </div>

            <form method="POST" action="foods.php">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" id="foodFormAction" value="add_food">
                <input type="hidden" name="food_id" id="foodFormId" value="0">

                <div class="input-group">
                    <label>FOOD NAME *</label>
                    <input type="text" name="food_name" id="modalFoodName" placeholder="e.g. Brown Rice, Grilled Chicken" required>
                </div>

                <div class="input-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 20px;">
                    <div class="input-group">
                        <label>SERVING SIZE</label>
                        <input type="text" name="serving_size" id="modalServingSize" placeholder="e.g. 100g, 1 cup" value="100g" required>
                    </div>
                    <div class="input-group">
                        <label>CALORIES (kcal) *</label>
                        <input type="number" step="0.1" name="calories" id="modalCalories" placeholder="e.g. 130" required>
                    </div>
                </div>

                <div class="macro-inputs-grid" style="margin-bottom: 24px;">
                    <div class="input-group macro-input-p">
                        <label>PROTEIN (g)</label>
                        <input type="number" step="0.1" name="protein" id="modalProtein" placeholder="0.0">
                    </div>
                    <div class="input-group macro-input-c">
                        <label>CARBS (g)</label>
                        <input type="number" step="0.1" name="carbohydrates" id="modalCarbs" placeholder="0.0">
                    </div>
                    <div class="input-group macro-input-f">
                        <label>FAT (g)</label>
                        <input type="number" step="0.1" name="fat" id="modalFat" placeholder="0.0">
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 24px;">
                    <label style="color: #1aae8d;">DIETARY FIBER (g)</label>
                    <input type="number" step="0.1" name="fiber" id="modalFiber" placeholder="0.0">
                </div>

                <button type="submit" class="primary-btn" id="modalSubmitBtn" style="margin-bottom: 0;">Save Food Item</button>
            </form>
        </div>
    </div>

    <script>
        function openAddFoodModal() {
            document.getElementById('modalFoodTitle').innerText = 'Add New Food Item';
            document.getElementById('foodFormAction').value = 'add_food';
            document.getElementById('foodFormId').value = '0';
            document.getElementById('modalFoodName').value = '';
            document.getElementById('modalServingSize').value = '100g';
            document.getElementById('modalCalories').value = '';
            document.getElementById('modalProtein').value = '';
            document.getElementById('modalCarbs').value = '';
            document.getElementById('modalFat').value = '';
            document.getElementById('modalFiber').value = '';
            document.getElementById('modalSubmitBtn').innerText = 'Add Food Item';

            document.getElementById('foodModal').classList.add('active');
        }

        function openEditFoodModal(food) {
            document.getElementById('modalFoodTitle').innerText = 'Edit ' + food.food_name;
            document.getElementById('foodFormAction').value = 'edit_food';
            document.getElementById('foodFormId').value = food.id;
            document.getElementById('modalFoodName').value = food.food_name;
            document.getElementById('modalServingSize').value = food.serving_size;
            document.getElementById('modalCalories').value = food.calories;
            document.getElementById('modalProtein').value = food.protein;
            document.getElementById('modalCarbs').value = food.carbohydrates;
            document.getElementById('modalFat').value = food.fat;
            document.getElementById('modalFiber').value = food.fiber;
            document.getElementById('modalSubmitBtn').innerText = 'Save Changes';

            document.getElementById('foodModal').classList.add('active');
        }

        function closeFoodModal() {
            document.getElementById('foodModal').classList.remove('active');
        }
    </script>
</body>
</html>
