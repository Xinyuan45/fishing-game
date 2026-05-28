<?php
session_start();
require 'db.php';

if ($_SESSION['user']['is_admin'] != 1) {
    exit("Access denied.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 新增或修改鱼种
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO fish_types (name, rarity, value) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['rarity'], $_POST['value']]);
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE fish_types SET name=?, rarity=?, value=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['rarity'], $_POST['value'], $_POST['id']]);
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM fish_types WHERE id=?");
        $stmt->execute([$_POST['id']]);
    }
}

// 读取所有鱼种
$fish_types = $pdo->query("SELECT * FROM fish_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Fish Types</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>🧰 Fish Type Management</h2>

    <form method="post" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="name" class="form-control" placeholder="Fish Name" required>
        </div>
        <div class="col-md-3">
            <select name="rarity" class="form-select" required>
                <option value="Common">Common</option>
                <option value="Rare">Rare</option>
                <option value="Epic">Epic</option>
                <option value="Legendary">Legendary</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="value" class="form-control" placeholder="Value" min="1" required>
        </div>
        <div class="col-md-2">
            <button name="add" class="btn btn-success w-100">Add</button>
        </div>
    </form>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Rarity</th>
                <th>Value</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fish_types as $fish): ?>
            <tr>
                <form method="post">
                    <td><?= $fish['id'] ?></td>
                    <td><input name="name" class="form-control" value="<?= htmlspecialchars($fish['name']) ?>"></td>
                    <td>
                        <select name="rarity" class="form-select">
                            <?php foreach (['Common','Rare','Epic','Legendary'] as $r): ?>
                                <option value="<?= $r ?>" <?= $fish['rarity']===$r?'selected':'' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="value" class="form-control" value="<?= $fish['value'] ?>"></td>
                    <td>
                        <input type="hidden" name="id" value="<?= $fish['id'] ?>">
                        <button name="edit" class="btn btn-primary btn-sm">Save</button>
                        <button name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete this fish?')">Delete</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>
</body>
</html>
