<?php
require_once '../includes/functions.php';

// التحقق من أن المستخدم أدمن
if (!is_admin()) {
    redirect('../login.php');
}

$page_title = "إدارة أماكن التواجد";
include 'partials/header.php';
$pdo = get_db_connection();
$message = '';

// معالجة الإضافة والتعديل والحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $address = sanitize_input($_POST['address']);
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $working_hours = sanitize_input($_POST['working_hours']);
    $phone = sanitize_input($_POST['phone']);

    if(isset($_POST['add_location'])){
        $stmt = $pdo->prepare("INSERT INTO locations (name, address, latitude, longitude, working_hours, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $address, $latitude, $longitude, $working_hours, $phone]);
        $message = "<div class='alert alert-success'>تم إضافة المكان بنجاح.</div>";
    }

    if(isset($_POST['edit_location'])){
        $id = intval($_POST['location_id']);
        $stmt = $pdo->prepare("UPDATE locations SET name=?, address=?, latitude=?, longitude=?, working_hours=?, phone=? WHERE id=?");
        $stmt->execute([$name, $address, $latitude, $longitude, $working_hours, $phone, $id]);
        $message = "<div class='alert alert-success'>تم تحديث المكان بنجاح.</div>";
    }
}
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<div class='alert alert-danger'>تم حذف المكان.</div>";
}


$locations = $pdo->query("SELECT * FROM locations ORDER BY id DESC")->fetchAll();
?>

<div class="page-header">
    <h1>إدارة أماكن التواجد</h1>
</div>
<?= $message ?>

<div class="card">
    <div class="card-header">
        <h3>إضافة/تعديل مكان</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="location_id" id="location_id">
            <div class="form-grid">
                <div class="form-group"><label>اسم المكان:</label><input type="text" name="name" id="name" required></div>
                <div class="form-group"><label>رقم الهاتف:</label><input type="text" name="phone" id="phone" required></div>
                <div class="form-group full-width"><label>العنوان:</label><input type="text" name="address" id="address" required></div>
                <div class="form-group"><label>خط العرض (Latitude):</label><input type="text" name="latitude" id="latitude" required placeholder="e.g., 30.0444"></div>
                <div class="form-group"><label>خط الطول (Longitude):</label><input type="text" name="longitude" id="longitude" required placeholder="e.g., 31.2357"></div>
                <div class="form-group full-width"><label>ساعات العمل:</label><input type="text" name="working_hours" id="working_hours" required placeholder="e.g., 9:00 ص - 5:00 م"></div>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_location" id="add_btn" class="btn btn-primary">إضافة مكان جديد</button>
                <button type="submit" name="edit_location" id="edit_btn" class="btn btn-success" style="display: none;">حفظ التعديلات</button>
                <button type="button" id="cancel_btn" class="btn btn-secondary" style="display: none;" onclick="resetForm()">إلغاء التعديل</button>
            </div>
        </form>
    </div>
</div>

<div class="table-container">
    <h2>الأماكن الحالية</h2>
    <table class="data-table">
        <thead>
            <tr><th>الاسم</th><th>العنوان</th><th>الهاتف</th><th>الإجراءات</th></tr>
        </thead>
        <tbody>
            <?php foreach($locations as $location): ?>
                <tr id="loc-<?= $location['id'] ?>">
                    <td data-field="name"><?= htmlspecialchars($location['name']) ?></td>
                    <td data-field="address"><?= htmlspecialchars($location['address']) ?></td>
                    <td data-field="phone"><?= htmlspecialchars($location['phone']) ?></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-primary" onclick="editLocation(<?= $location['id'] ?>)">تعديل</button>
                        <a href="?delete=<?= $location['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                        <span style="display:none;" data-field="latitude"><?= $location['latitude'] ?></span>
                        <span style="display:none;" data-field="longitude"><?= $location['longitude'] ?></span>
                        <span style="display:none;" data-field="working_hours"><?= $location['working_hours'] ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function editLocation(id) {
        const row = document.getElementById(`loc-${id}`);
        document.getElementById('location_id').value = id;
        document.getElementById('name').value = row.querySelector('[data-field="name"]').textContent;
        document.getElementById('address').value = row.querySelector('[data-field="address"]').textContent;
        document.getElementById('phone').value = row.querySelector('[data-field="phone"]').textContent;
        document.getElementById('latitude').value = row.querySelector('[data-field="latitude"]').textContent;
        document.getElementById('longitude').value = row.querySelector('[data-field="longitude"]').textContent;
        document.getElementById('working_hours').value = row.querySelector('[data-field="working_hours"]').textContent;
        
        document.getElementById('add_btn').style.display = 'none';
        document.getElementById('edit_btn').style.display = 'inline-block';
        document.getElementById('cancel_btn').style.display = 'inline-block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function resetForm(){
        document.querySelector('form').reset();
        document.getElementById('location_id').value = '';
        document.getElementById('add_btn').style.display = 'inline-block';
        document.getElementById('edit_btn').style.display = 'none';
        document.getElementById('cancel_btn').style.display = 'none';
    }
</script>

<?php include 'partials/footer.php'; ?>