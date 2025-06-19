<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: admin.php");
    exit();
}

$feedback = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $targetDir = "uploads/";
    $albumType = isset($_POST['album_type']) ? $_POST['album_type'] : '';
    $subDir = '';

    // Determine subdirectory based on album type
    if ($albumType === "wedding") {
        $subDir = "wedding/";
    } else if ($albumType === "debut") {
        $subDir = "debut/";
    }

    // Ensure subdirectory exists
    if (!empty($subDir)) {
        if (!is_dir($targetDir . $subDir)) {
            mkdir($targetDir . $subDir, 0777, true);
        }
        $targetDir = $targetDir . $subDir;
    }

    $targetFile = $targetDir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "gif", "webp"];

    // Check file type
    if (!in_array($imageFileType, $allowedTypes)) {
        $feedback = "<div class='alert danger'>Only JPG, JPEG, PNG, GIF & WEBP files are allowed.</div>";
    } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
        $feedback = "<div class='alert danger'>Sorry, your file is too large (max 5MB).</div>";
    } elseif (empty($albumType)) {
        $feedback = "<div class='alert danger'>Please select an album type (Wedding or Debut).</div>";
    } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $feedback = "<div class='alert success'>Image uploaded successfully to <b>" . htmlspecialchars(ucfirst($albumType)) . "</b> album!</div>";
    } else {
        $feedback = "<div class='alert danger'>Sorry, there was an error uploading your file.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Image - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f7f8fa; margin: 0; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: #234b3a; color: #fff; display: flex; flex-direction: column; padding-top: 30px; position: fixed; height: 100%; left: 0; top: 0; z-index: 10; }
        .sidebar .logo { font-size: 1.7rem; font-weight: bold; text-align: center; padding-bottom: 30px; }
        .sidebar nav a { color: #fff; display: flex; align-items: center; padding: 16px 32px; text-decoration: none; font-size: 1.04rem; border-left: 5px solid transparent; transition: background 0.15s, border-color 0.15s; }
        .sidebar nav a.active, .sidebar nav a:hover { background: rgba(255,255,255,0.09); border-left: 5px solid #f8b400; }
        .sidebar nav a .fa-fw { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 220px; padding: 40px 30px 30px 30px; flex: 1; }
        .section-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.09); padding: 26px 30px 24px 30px; max-width: 440px; margin: 60px auto; }
        .section-title { font-size: 1.3rem; font-weight: 600; color: #234b3a; margin-bottom: 18px; }
        .alert { margin-bottom: 20px; padding: 12px 18px; border-radius: 7px; font-size: 1rem; }
        .alert.success { background: #eafaf1; color: #27ae60; }
        .alert.danger { background: #fdeaea; color: #e74c3c; }
        label { font-weight: 700; color: #234b3a; display: block; margin-top: 14px; }
        input[type="file"], select { margin-top: 8px; }
        button { margin-top: 20px; background: #234b3a; color: #fff; padding: 10px 18px; border-radius: 8px; border: none; font-size: 1rem; cursor: pointer; }
        button:hover { background: #1a372a; }
        .dashboard-actions { margin-top: 20px; }
        .dashboard-actions a { background: #234b3a; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-size: 1rem; margin-left: 12px; text-decoration: none; transition: background 0.2s; }
        .dashboard-actions a:hover { background: #1a372a; }
        @media (max-width: 700px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-leaf"></i> <br>The BearFruits Admin</br>
        </div>
        <nav>
            <a href="dashboard.php"><i class="fa-fw fa-solid fa-calendar-check"></i>Bookings</a>
            <a href="dashboard.php#accepted"><i class="fa-fw fa-solid fa-check"></i>Accepted</a>
            <a href="dashboard.php#security"><i class="fa-fw fa-solid fa-user-shield"></i>Security</a>
            <a href="upload_image.php" class="active"><i class="fa-fw fa-solid fa-image"></i>Upload Image</a>
            <a href="logout.php" class="dashboard-actions"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="section-card">
            <div class="section-title"><i class="fa fa-image"></i> Upload New Image</div>
            <?php if (!empty($feedback)) echo $feedback; ?>
            <form method="POST" enctype="multipart/form-data">
                <label for="album_type">Album Type:</label>
                <select name="album_type" id="album_type" required>
                    <option value="">-- Select Album --</option>
                    <option value="wedding" <?php if (isset($_POST['album_type']) && $_POST['album_type']=='wedding') echo 'selected'; ?>>Wedding</option>
                    <option value="debut" <?php if (isset($_POST['album_type']) && $_POST['album_type']=='debut') echo 'selected'; ?>>Debut</option>
                </select>
                <label for="image">Select image to upload:</label>
                <input type="file" name="image" id="image" required accept="image/*">
                <button type="submit">Upload Image</button>
            </form>
        </div>
        <div class="dashboard-actions">
            <a href="dashboard.php"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </main>
</div>
</body>
</html>