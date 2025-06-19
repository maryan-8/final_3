<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: admin.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "bearfruitsstudios";
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$feedback = "";

// --- DETERMINE WHICH SECTION TO SHOW (function) ---
$current_section = 'home';
// prioritize: POST > GET > default
if (isset($_POST['current_section'])) {
    $current_section = $_POST['current_section'];
} elseif (isset($_GET['section'])) {
    $current_section = $_GET['section'];
}

/* ---------------------
   ALBUM/FOLDER SYSTEM (PARTED INTO WEDDING/DEBUT)
   --------------------- */
$conn->query("CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    type ENUM('wedding','debut') NOT NULL DEFAULT 'wedding'
)");

// --- ADD ALBUM ---
if (isset($_POST['add_album']) && !empty($_POST['album_name']) && !empty($_POST['album_type'])) {
    $album_name = trim($_POST['album_name']);
    $album_type = $_POST['album_type'] === 'debut' ? 'debut' : 'wedding'; // Only wedding or debut allowed
    $stmt = $conn->prepare("INSERT INTO albums (name, type) VALUES (?, ?)");
    $stmt->bind_param("ss", $album_name, $album_type);
    if ($stmt->execute()) {
        $album_id = $stmt->insert_id;
        $dir = "uploads/albums/$album_id";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $feedback = "<div class='alert success'>Album created!</div>";
    } else {
        $feedback = "<div class='alert danger'>Failed to create album.</div>";
    }
    $stmt->close();
    $current_section = "upload"; // Stay on upload section
}

// --- RENAME ALBUM ---
if (isset($_POST['rename_album']) && isset($_POST['album_id']) && isset($_POST['new_album_name'])) {
    $album_id = intval($_POST['album_id']);
    $new_name = trim($_POST['new_album_name']);
    // Don't rename to empty or only whitespace
    if ($new_name !== "") {
        $stmt = $conn->prepare("UPDATE albums SET name=? WHERE id=?");
        $stmt->bind_param("si", $new_name, $album_id);
        if ($stmt->execute()) {
            $feedback = "<div class='alert success'>Album renamed!</div>";
        } else {
            $feedback = "<div class='alert danger'>Failed to rename album.</div>";
        }
        $stmt->close();
    } else {
        $feedback = "<div class='alert danger'>Album name cannot be empty.</div>";
    }
    $current_section = "upload";
}

// --- CHANGE ALBUM TYPE (CATEGORY) ---
if (isset($_POST['change_album_type']) && isset($_POST['album_id']) && isset($_POST['album_type'])) {
    $album_id = intval($_POST['album_id']);
    $album_type = $_POST['album_type'] === 'debut' ? 'debut' : 'wedding';
    $stmt = $conn->prepare("UPDATE albums SET type=? WHERE id=?");
    $stmt->bind_param("si", $album_type, $album_id);
    if ($stmt->execute()) {
        $feedback = "<div class='alert success'>Album type updated!</div>";
    } else {
        $feedback = "<div class='alert danger'>Failed to update album type.</div>";
    }
    $stmt->close();
    $current_section = "upload";
}

// --- DELETE ALBUM ---
if (isset($_POST['delete_album']) && isset($_POST['album_id'])) {
    $album_id = intval($_POST['album_id']);
    $stmt = $conn->prepare("DELETE FROM albums WHERE id=?");
    $stmt->bind_param("i", $album_id);
    if ($stmt->execute()) {
        $dir = "uploads/albums/$album_id";
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($dir);
        }
        $feedback = "<div class='alert success'>Album deleted!</div>";
    } else {
        $feedback = "<div class='alert danger'>Failed to delete album.</div>";
    }
    $stmt->close();
    $current_section = "upload";
}

// --- UPLOAD ALBUM THUMBNAIL ---
if (isset($_POST['upload_thumb']) && isset($_POST['album_id']) && isset($_FILES['thumb'])) {
    $album_id = intval($_POST['album_id']);
    $dir = "uploads/albums/$album_id";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $thumb_name = "thumb." . pathinfo($_FILES['thumb']['name'], PATHINFO_EXTENSION);
    $thumb_path = "$dir/$thumb_name";
    $allowedTypes = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($thumb_name, PATHINFO_EXTENSION));
    if (in_array($ext, $allowedTypes)) {
        if (move_uploaded_file($_FILES['thumb']['tmp_name'], $thumb_path)) {
            $stmt = $conn->prepare("UPDATE albums SET thumbnail=? WHERE id=?");
            $stmt->bind_param("si", $thumb_name, $album_id);
            $stmt->execute();
            $stmt->close();
            $feedback = "<div class='alert success'>Thumbnail updated!</div>";
        } else {
            $feedback = "<div class='alert danger'>Failed to upload thumbnail.</div>";
        }
    } else {
        $feedback = "<div class='alert danger'>Invalid thumbnail file type.</div>";
    }
    $current_section = "upload";
}

// --- ADD IMAGE TO ALBUM ---
if (isset($_POST['upload_album_image_folder']) && isset($_POST['album_id']) && isset($_FILES['album_image'])) {
    $album_id = intval($_POST['album_id']);
    $dir = "uploads/albums/$album_id";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $fileName = time() . "_" . basename($_FILES["album_image"]["name"]);
    $targetFile = "$dir/$fileName";
    $allowedTypes = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if (in_array($ext, $allowedTypes)) {
        if (move_uploaded_file($_FILES["album_image"]["tmp_name"], $targetFile)) {
            $feedback = "<div class='alert success'>Image uploaded to album!</div>";
        } else {
            $feedback = "<div class='alert danger'>Failed to upload image.</div>";
        }
    } else {
        $feedback = "<div class='alert danger'>Invalid image file type.</div>";
    }
    $current_section = "upload";
}

// --- DELETE IMAGE FROM ALBUM ---
if (isset($_POST['delete_album_image']) && isset($_POST['album_id']) && isset($_POST['image'])) {
    $album_id = intval($_POST['album_id']);
    $image = $_POST['image'];
    $path = "uploads/albums/$album_id/$image";
    if (is_file($path)) {
        if (unlink($path)) {
            $feedback = "<div class='alert success'>Image deleted from album.</div>";
        } else {
            $feedback = "<div class='alert danger'>Failed to delete image.</div>";
        }
    }
    $current_section = "upload";
}

// --- GET ALBUMS BY TYPE ---
function get_album_images($album_id) {
    $dir = "uploads/albums/$album_id";
    $images = [];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            if (strpos($file, "thumb.") === 0) continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $images[] = $file;
        }
    }
    return $images;
}
$albumRowsWedding = [];
$albumRowsDebut = [];
$resWedding = $conn->query("SELECT * FROM albums WHERE type='wedding' ORDER BY id DESC");
if ($resWedding) while ($row = $resWedding->fetch_assoc()) $albumRowsWedding[] = $row;
$resDebut = $conn->query("SELECT * FROM albums WHERE type='debut' ORDER BY id DESC");
if ($resDebut) while ($row = $resDebut->fetch_assoc()) $albumRowsDebut[] = $row;

/* ---------------------
   END ALBUM/FOLDER SYSTEM
   --------------------- */

// --- BOOKINGS LOGIC ---
if (isset($_POST['update_booking'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $service = $_POST['service'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("UPDATE bookings SET name = ?, email = ?, phone = ?, service = ?, message = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $email, $phone, $service, $message, $id);

    if ($stmt->execute()) {
        $feedback = "<div class='alert success'>Booking updated successfully!</div>";
    } else {
        $feedback = "<div class='alert danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
    $current_section = "bookings";
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $feedback = "<div class='alert success'>Booking deleted successfully!</div>";
    } else {
        $feedback = "<div class='alert danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
    $current_section = "bookings";
}

if (isset($_GET['accept'])) {
    $id = $_GET['accept'];
    $stmt = $conn->prepare("SELECT name, email, phone, booking_date FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($name, $email, $phone, $booking_date);
    if ($stmt->fetch()) {
        $stmt->close();
        $stmt2 = $conn->prepare("INSERT INTO bookings2 (name, email, phone, booking_date) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("ssss", $name, $email, $phone, $booking_date);
        if ($stmt2->execute()) {
            $stmt2->close();
            $stmt3 = $conn->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt3->bind_param("i", $id);
            if ($stmt3->execute()) {
                $feedback = "<div class='alert success'>Booking accepted!</div>";
            } else {
                $feedback = "<div class='alert danger'>Error: " . $stmt3->error . "</div>";
            }
            $stmt3->close();
        } else {
            $feedback = "<div class='alert danger'>Error: " . $stmt2->error . "</div>";
            $stmt2->close();
        }
    } else {
        $feedback = "<div class='alert danger'>Error: Booking not found.</div>";
        $stmt->close();
    }
    $current_section = "bookings";
}

if (isset($_GET['done'])) {
    $id = $_GET['done'];
    $stmt = $conn->prepare("DELETE FROM bookings2 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $feedback = "<div class='alert success'>Booking marked as done!</div>";
    } else {
        $feedback = "<div class='alert danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
    $current_section = "accepted";
}

$sql = "SELECT * FROM bookings";
$result = $conn->query($sql);

$sql2 = "SELECT * FROM bookings2";
$result2 = $conn->query($sql2);

// -------------------
// DASHBOARD SUMMARY COUNTS
// -------------------
$total_bookings_count = 0;
$total_accepted_count = 0;
$total_images_count = 0;

// Bookings count
$bookings_count_res = $conn->query("SELECT COUNT(*) AS cnt FROM bookings");
if ($bookings_count_res && $row = $bookings_count_res->fetch_assoc()) {
    $total_bookings_count = $row['cnt'];
}

// Accepted count
$accepted_count_res = $conn->query("SELECT COUNT(*) AS cnt FROM bookings2");
if ($accepted_count_res && $row = $accepted_count_res->fetch_assoc()) {
    $total_accepted_count = $row['cnt'];
}

// Uploaded images
$images_count = 0;
foreach (array_merge($albumRowsWedding, $albumRowsDebut) as $album) {
    $images_count += count(get_album_images($album['id']));
}
$total_images_count = $images_count;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The BearFruits Studios</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
       :root {
            --primary: #234b3a;
            --accent: #f8b400;
            --light: #f7f8fa;
            --danger: #e74c3c;
            --success: #27ae60;
            --sidebar-width: 220px;
        }
        html { box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }
        body { font-family: 'Roboto', Arial, sans-serif; margin: 0; background: var(--light); }
        .dashboard { display: flex; min-height: 100vh; background: var(--light);}
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding-top: 30px;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
            z-index: 10;
            transition: transform 0.2s;
        }
        .sidebar .logo {
            font-size: 1.7rem;
            font-weight: bold;
            text-align: center;
            padding-bottom: 30px;
            letter-spacing: 1px;
        }
        .sidebar nav a {
            color: #fff;
            display: flex;
            align-items: center;
            padding: 16px 32px;
            text-decoration: none;
            font-size: 1.04rem;
            border-left: 5px solid transparent;
            transition: background 0.15s, border-color 0.15s;
        }
        .sidebar nav a.active, .sidebar nav a:hover {
            background: rgba(255,255,255,0.09);
            border-left: 5px solid var(--accent);
        }
        .sidebar nav a .fa-fw { width: 25px; margin-right: 10px;}
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px 30px 30px 30px;
            flex: 1;
            width: 100%;
            max-width: 100vw;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }
        .dashboard-title {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary);
        }
        .dashboard-actions a {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 1rem;
            margin-left: 12px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .dashboard-actions a:hover { background: #1a372a;}
        .section-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.09);
            padding: 26px 30px 24px 30px;
            margin-bottom: 30px;
            overflow-x: auto;
            display: none;
        }
        .section-card.active { display: block;}
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 18px;
        }
        .alert {
            margin-bottom: 20px;
            padding: 12px 18px;
            border-radius: 7px;
            font-size: 1rem;
        }
        .alert.success { background: #eafaf1; color: var(--success);}
        .alert.danger { background: #fdeaea; color: var(--danger);}
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px;}
        th, td { padding: 11px 10px; text-align: left; border-bottom: 1px solid #eee;}
        th { background: var(--primary); color: #fff; font-weight: 700;}
        tr:nth-child(even) { background: #f2f4f7;}
        tr:hover { background: #f7f7f7;}
        .table-actions a, .table-actions button {
            margin: 0 4px;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.97rem;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .edit-btn { background: #fffbe7; color: #c58700; border: 1px solid #f8b400;}
        .edit-btn:hover { background: #f8e3a3;}
        .delete-btn { background: #fdeaea; color: var(--danger); border: 1px solid #e74c3c;}
        .delete-btn:hover { background: #f9c9c5;}
        .accept-btn { background: #eafaf1; color: var(--success); border: 1px solid #27ae60;}
        .accept-btn:hover { background: #c5f3d8;}
        .done-btn { background: #e0e9fd; color: #1252c6; border: 1px solid #366cd2;}
        .done-btn:hover { background: #c9deff;}
        .image-list-table { width: 100%; border-collapse: collapse; margin-top: 15px;}
        .image-list-table th, .image-list-table td { border: 1px solid #eee; padding: 7px 10px; text-align: left;}
        .image-thumb { max-width: 80px; max-height: 70px; border-radius: 3px; box-shadow: 1px 1px 3px #ccc;}
        .delete-image-btn {
            background: #fdeaea; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 14px; border-radius: 5px; cursor: pointer;
        }
        .delete-image-btn:hover { background: #f9c9c5;}
        .modal, .modal-overlay { display: none;}
        .modal-overlay.show {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 100;
        }
        .modal.show {
            display: block;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            border-radius: 14px;
            z-index: 101;
            width: 95vw;
            max-width: 470px;
            padding: 36px 30px 30px 30px;
        }
        .modal form { display: flex; flex-direction: column; gap: 14px;}
        .modal label { font-weight: 700; color: var(--primary);}
        .modal input, .modal textarea {
            padding: 9px 11px;
            border-radius: 5px;
            border: 1px solid #bbb;
            font-size: 1rem;
        }
        .modal textarea { min-height: 80px;}
        .modal .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 13px;}
        .modal .modal-actions button { padding: 7px 16px; border-radius: 5px; font-weight: 600; border: none; font-size: 1rem; cursor: pointer; transition: background 0.2s;}
        .modal .save-btn { background: var(--primary); color: #fff;}
        .modal .cancel-btn { background: #bbb; color: #fff;}
        .modal .save-btn:hover { background: #18412b;}
        .modal .cancel-btn:hover { background: #999;}
        @media (max-width: 900px) {
            .main-content { padding: 30px 4vw 8vw 4vw;}
            .section-card { padding: 12px 2vw;}
            .dashboard-header { flex-direction: column; gap: 12px;}
            .sidebar { font-size: 0.98rem;}
        }
        @media (max-width: 700px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                left: 0;
                top: 0;
                height: 100%;
                z-index: 1001;
                width: 75vw;
                max-width: 300px;
                transition: transform 0.2s;
                box-shadow: 2px 0 8px rgba(0,0,0,0.07);
            }
            .sidebar.active { transform: translateX(0);}
            .main-content { margin-left: 0;}
            .mobile-navbar {
                display: flex;
                background: var(--primary);
                color: #fff;
                align-items: center;
                padding: 10px 16px;
                position: sticky;
                top: 0;
                z-index: 20;
            }
            .mobile-navbar .menu-btn {
                font-size: 1.6rem;
                margin-right: 18px;
                background: none;
                border: none;
                color: #fff;
                cursor: pointer;
            }
            .mobile-navbar .logo {
                font-size: 1.15rem;
                font-weight: bold;
                flex: 1;
            }
        }
        @media (max-width: 700px) {
            table, .image-list-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
                font-size: 0.92rem;
            }
            th, td {
                min-width: 100px;
                padding: 8px 7px;
                word-break: break-word;
            }
        }
        @media (max-width: 480px) {
            .dashboard-title, .section-title { font-size: 1.17rem;}
            .dashboard-header, .dashboard-actions { flex-direction: column; gap: 8px;}
            .image-thumb { max-width: 55px; max-height: 45px;}
            .modal.show { padding: 12px 2vw 15px 2vw;}
            .modal label, .modal input, .modal textarea { font-size: 0.97rem;}
        }
        .dashboard-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(230px,1fr));
            gap: 24px;
            margin-bottom: 34px;
        }
        .dashboard-summary-card {
            background: #fff;
            border-radius: 13px;
            box-shadow: 0 4px 14px rgba(44,62,80,0.10);
            padding: 25px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dashboard-summary-card .summary-title {
            font-size: 1.05rem;
            color: #234b3a;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .dashboard-summary-card .summary-count {
            font-size: 2.1rem;
            font-weight: bold;
            color: #27ae60;
        }
        .dashboard-summary-card .summary-icon {
            font-size: 2.3rem;
            color: #f8b400;
            background: #fdf6e3;
            padding: 13px;
            border-radius: 50%;
        }
        @media (max-width: 650px) {
            .dashboard-summary-grid { gap: 14px;}
            .dashboard-summary-card { padding: 14px 10px;}
            .dashboard-summary-card .summary-count { font-size: 1.3rem;}
            .dashboard-summary-card .summary-title { font-size: 0.99rem;}
        }
        .img-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .img-item { position: relative; display: inline-block; }
        .album-folder-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 1px 1px 3px #ccc;
            display: block;
            background: #f5f5f5;
        }
        @media (max-width: 650px) {
            .album-folder-img { width: 48px; height: 36px; }
        }
    </style>
</head>
<body>
<div class="mobile-navbar" style="display:none">
    <button class="menu-btn" id="openSidebar" aria-label="Open sidebar"><i class="fa fa-bars"></i></button>
    <span class="logo"><i class="fa-solid fa-leaf"></i> The BearFruits Admin</span>
</div>
<div class="dashboard">
     <aside class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i> <br>The BearFruits Admin</br>
            </div>
            <nav>
                <a href="#" class="active" id="nav-home"><i class="fa-fw fa-solid fa-house"></i>Home</a>
                <a href="#" id="nav-bookings"><i class="fa-fw fa-solid fa-calendar-check"></i>Bookings</a>
                <a href="#" id="nav-accepted"><i class="fa-fw fa-solid fa-check"></i>Accepted</a>
                <a href="#" id="nav-security"><i class="fa-fw fa-solid fa-user-shield"></i>Security</a>
                <a href="#" id="nav-upload"><i class="fa-fw fa-solid fa-image"></i>Upload Image</a>
                <a href="logout.php" class="dashboard-actions"><i class="fa fa-sign-out-alt"></i> Logout</a>
            </nav>
            <div style="margin-top:40px;padding:0 18px;">
                <div class="dashboard-summary-card" style="margin-bottom:12px;">
                    <span>
                        <div class="summary-title">Bookings</div>
                        <div class="summary-count"><?= $total_bookings_count ?></div>
                    </span>
                    <span class="summary-icon"><i class="fa-solid fa-calendar-check"></i></span>
                </div>
                <div class="dashboard-summary-card" style="margin-bottom:12px;">
                    <span>
                        <div class="summary-title">Accepted</div>
                        <div class="summary-count"><?= $total_accepted_count ?></div>
                    </span>
                    <span class="summary-icon"><i class="fa-solid fa-check"></i></span>
                </div>
                <div class="dashboard-summary-card">
                    <span>
                        <div class="summary-title">Uploaded Images</div>
                        <div class="summary-count"><?= $total_images_count ?></div>
                    </span>
                    <span class="summary-icon"><i class="fa-solid fa-image"></i></span>
                </div>
            </div>
        </aside>
    <main class="main-content">
        <div class="dashboard-header">
            <span class="dashboard-title" id="dashboard-title">Dashboard Home</span>
            <div class="dashboard-actions">
                <a href="#" id="refreshBtn"><i class="fa fa-rotate"></i> Refresh</a>
            </div>
        </div>
        <?php if (!empty($feedback)) echo $feedback; ?>

        <div class="section-card<?= $current_section == 'home' ? ' active': '' ?>" id="section-home">
             <div class="section-title"><i class="fa fa-house"></i> Overview</div>
            <div class="dashboard-summary-grid">
                <div class="dashboard-summary-card">
                    <span>
                        <div class="summary-title">Bookings</div>
                        <div class="summary-count"><?= $total_bookings_count ?></div>
                    </span>
                    <span class="summary-icon"><i class="fa-solid fa-calendar-check"></i></span>
                </div>
                <div class="dashboard-summary-card">
                    <span>
                        <div class="summary-title">Accepted</div>
                        <div class="summary-count"><?= $total_accepted_count ?></div>
                    </span>
                    <span class="summary-icon"><i class="fa-solid fa-check"></i></span>
                </div>
                <div class="dashboard-summary-card">
                    <span>
                        <div class="summary-title">Uploaded Images</div>
                        <div class="summary-count"><?= $total_images_count ?></div>
                    </span>
                    <span class="summary-icon"><i class="fa-solid fa-image"></i></span>
                </div>
            </div>
            <div style="margin-top: 20px;">
                <p>Welcome to your admin dashboard. Use the side menu to manage bookings, albums, and security.</p>
            </div>
        </div>
        
        <!-- UPLOAD SECTION, PARTED INTO WEDDING/DEBUT -->
        <div class="section-card<?= $current_section == 'upload' ? ' active': '' ?>" id="section-upload">
            <h3 style="margin-bottom:10px; color:#234b3a;">Wedding Albums</h3>
            <form method="post" style="margin-bottom:20px;">
                <input type="hidden" name="current_section" value="upload">
                <input type="text" name="album_name" placeholder="New Wedding Album Name" required>
                <input type="hidden" name="album_type" value="wedding">
                <button type="submit" name="add_album" class="save-btn">Add Wedding Album</button>
            </form>
            <table class="album-table">
                <tr>
                    <th>Thumbnail</th>
                    <th>Name</th>
                    <th>Rename</th>
                    <th>Album Type</th>
                    <th>Upload Thumbnail</th>
                    <th>Add Image</th>
                    <th>Images</th>
                    <th>Delete</th>
                </tr>
                <?php foreach ($albumRowsWedding as $album): 
                    $thumb = $album['thumbnail'] ? "uploads/albums/{$album['id']}/{$album['thumbnail']}" : "https://via.placeholder.com/60x60?text=No+Thumb";
                ?>
                <tr>
                    <td>
                        <img src="<?php echo $thumb ?>" class="image-thumb">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($album['name']); ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <input type="text" name="new_album_name" value="<?php echo htmlspecialchars($album['name']); ?>" required>
                            <button type="submit" name="rename_album" class="edit-btn">Rename</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <select name="album_type" onchange="this.form.submit()">
                                <option value="wedding"<?= $album['type']=='wedding' ? ' selected':'' ?>>Wedding</option>
                                <option value="debut"<?= $album['type']=='debut' ? ' selected':'' ?>>Debut</option>
                            </select>
                            <input type="hidden" name="change_album_type" value="1">
                        </form>
                    </td>
                    <td>
                        <form method="post" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <input type="file" name="thumb" accept="image/*" required>
                            <button type="submit" name="upload_thumb" class="save-btn">Upload</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <input type="file" name="album_image" accept="image/*" required>
                            <button type="submit" name="upload_album_image_folder" class="save-btn">Add Image</button>
                        </form>
                    </td>
                    <td>
                        <div class="img-list">
                        <?php foreach(get_album_images($album['id']) as $img): ?>   
                            <div class="img-item">
                                <img src="uploads/albums/<?php echo $album['id'].'/'.urlencode($img); ?>"
                                    class="album-folder-img"
                                    alt="Album Image">
                                <form method="post" style="position:absolute;top:2px;right:2px;">
                                    <input type="hidden" name="current_section" value="upload">
                                    <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                                    <input type="hidden" name="image" value="<?php echo htmlspecialchars($img); ?>">
                                    <button type="submit" name="delete_album_image" onclick="return confirm('Delete image?');" style="background: #e74c3c; color: #fff; border:none; border-radius:3px;">x</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete album and all its images?');">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <button type="submit" name="delete_album" style="background:#e74c3c;color:#fff;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <hr style="margin:35px 0 24px 0;">
            <h3 style="margin-bottom:10px; color:#234b3a;">Debut Albums</h3>
            <form method="post" style="margin-bottom:20px;">
                <input type="hidden" name="current_section" value="upload">
                <input type="text" name="album_name" placeholder="New Debut Album Name" required>
                <input type="hidden" name="album_type" value="debut">
                <button type="submit" name="add_album" class="save-btn">Add Debut Album</button>
            </form>
            <table class="album-table">
                <tr>
                    <th>Thumbnail</th>
                    <th>Name</th>
                    <th>Rename</th>
                    <th>Album Type</th>
                    <th>Upload Thumbnail</th>
                    <th>Add Image</th>
                    <th>Images</th>
                    <th>Delete</th>
                </tr>
                <?php foreach ($albumRowsDebut as $album): 
                    $thumb = $album['thumbnail'] ? "uploads/albums/{$album['id']}/{$album['thumbnail']}" : "https://via.placeholder.com/60x60?text=No+Thumb";
                ?>
                <tr>
                    <td>
                        <img src="<?php echo $thumb ?>" class="image-thumb">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($album['name']); ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <input type="text" name="new_album_name" value="<?php echo htmlspecialchars($album['name']); ?>" required>
                            <button type="submit" name="rename_album" class="edit-btn">Rename</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <select name="album_type" onchange="this.form.submit()">
                                <option value="wedding"<?= $album['type']=='wedding' ? ' selected':'' ?>>Wedding</option>
                                <option value="debut"<?= $album['type']=='debut' ? ' selected':'' ?>>Debut</option>
                            </select>
                            <input type="hidden" name="change_album_type" value="1">
                        </form>
                    </td>
                    <td>
                        <form method="post" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <input type="file" name="thumb" accept="image/*" required>
                            <button type="submit" name="upload_thumb" class="save-btn">Upload</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <input type="file" name="album_image" accept="image/*" required>
                            <button type="submit" name="upload_album_image_folder" class="save-btn">Add Image</button>
                        </form>
                    </td>
                    <td>
                        <div class="img-list">
                        <?php foreach(get_album_images($album['id']) as $img): ?>   
                            <div class="img-item">
                                <img src="uploads/albums/<?php echo $album['id'].'/'.urlencode($img); ?>"
                                    class="album-folder-img"
                                    alt="Album Image">
                                <form method="post" style="position:absolute;top:2px;right:2px;">
                                    <input type="hidden" name="current_section" value="upload">
                                    <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                                    <input type="hidden" name="image" value="<?php echo htmlspecialchars($img); ?>">
                                    <button type="submit" name="delete_album_image" onclick="return confirm('Delete image?');" style="background: #e74c3c; color: #fff; border:none; border-radius:3px;">x</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete album and all its images?');">
                            <input type="hidden" name="current_section" value="upload">
                            <input type="hidden" name="album_id" value="<?php echo $album['id']; ?>">
                            <button type="submit" name="delete_album" style="background:#e74c3c;color:#fff;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="section-card<?= $current_section == 'bookings' ? ' active': '' ?>" id="section-bookings">
             <div class="section-title"><i class="fa fa-hourglass-start"></i> Pending Bookings</div>
            <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th><th>Email</th><th>Phone</th><th>Service</th>
                        <th>Message</th><th>Booking Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr data-booking='<?php echo json_encode($row); ?>'>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['service']); ?></td>
                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                        <td><?php echo $row['booking_date']; ?></td>
                        <td class="table-actions">
                            <a href="#" class="edit-btn" data-id="<?php echo $row['id']; ?>"><i class="fa fa-pencil"></i> Edit</a>
                            <a href="?delete=<?php echo $row['id']; ?>&section=bookings" class="delete-btn" onclick="return confirm('Delete this booking?');"><i class="fa fa-trash"></i> Delete</a>
                            <a href="?accept=<?php echo $row['id']; ?>&section=bookings" class="accept-btn" onclick="return confirm('Accept this booking?');"><i class="fa fa-check"></i> Accept</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="alert">No new bookings found.</div>
            <?php endif; ?>
        </div>
        <div class="section-card<?= $current_section == 'accepted' ? ' active': '' ?>" id="section-accepted">
             <div class="section-title"><i class="fa fa-check"></i> Accepted Bookings</div>
            <?php if ($result2->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th><th>Email</th><th>Phone</th><th>Booking Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row2 = $result2->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row2['name']); ?></td>
                        <td><?php echo htmlspecialchars($row2['email']); ?></td>
                        <td><?php echo htmlspecialchars($row2['phone']); ?></td>
                        <td><?php echo $row2['booking_date']; ?></td>
                        <td class="table-actions">
                            <a href="?done=<?php echo $row2['id']; ?>&section=accepted" class="done-btn" onclick="return confirm('Mark this booking as done?');"><i class="fa fa-check-double"></i> Done</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="alert">No accepted bookings found.</div>
            <?php endif; ?>
        </div>
        <div class="section-card<?= $current_section == 'security' ? ' active': '' ?>" id="section-security">
              <div class="section-title"><i class="fa fa-user-shield"></i> Security Manager</div>
            <form id="securityForm" method="POST" action="update_admin.php">
                <label for="username">New Username:</label>
                <input type="text" id="username" name="username" required />
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required />
                <div style="margin-top: 12px;">
                    <button type="submit" class="save-btn">Update Credentials</button>
                    <button type="button" class="cancel-btn" id="resetBtn">Reset Password</button>
                </div>
            </form>
            <div class="alert" style="margin-top:20px;">Security manager functionality coming soon.</div>
        </div>
    </main>
</div>
<div class="modal-overlay" id="modal-overlay"></div>
<div class="modal" id="editModal">
     <h2>Edit Booking</h2>
    <form method="POST" id="editForm">
        <input type="hidden" name="id" id="edit-id">
        <label>Name</label>
        <input type="text" name="name" id="edit-name" required>
        <label>Email</label>
        <input type="email" name="email" id="edit-email" required>
        <label>Phone</label>
        <input type="text" name="phone" id="edit-phone" required>
        <label>Service</label>
        <input type="text" name="service" id="edit-service" required>
        <label>Message</label>
        <textarea name="message" id="edit-message" required></textarea>
        <div class="modal-actions">
            <button type="submit" name="update_booking" class="save-btn">Save</button>
            <button type="button" class="cancel-btn" id="closeEditModal">Cancel</button>
        </div>
    </form>
</div>
<script>
    let currentSection = "<?= $current_section ?>";
    function checkMobile() {
        if (window.innerWidth <= 700) {
            document.getElementById('sidebar').classList.remove('active');
            document.querySelector('.mobile-navbar').style.display = 'flex';
        } else {
            document.getElementById('sidebar').classList.add('active');
            document.querySelector('.mobile-navbar').style.display = 'none';
        }
    }
    window.addEventListener('resize', checkMobile);
    window.addEventListener('DOMContentLoaded', function() {
        checkMobile();
        showSection(currentSection);
    });
    document.getElementById('openSidebar').onclick = function(e) {
        e.stopPropagation();
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.add('active');
        function closeSidebar(ev) {
            if (!sidebar.contains(ev.target) && ev.target !== document.getElementById('openSidebar')) {
                sidebar.classList.remove('active');
                document.body.removeEventListener('click', closeSidebar);
            }
        }
        setTimeout(() => document.body.addEventListener('click', closeSidebar), 0);
    };
    const navHome = document.getElementById('nav-home');
    const navBookings = document.getElementById('nav-bookings');
    const navAccepted = document.getElementById('nav-accepted');
    const navSecurity = document.getElementById('nav-security');
    const navUpload = document.getElementById('nav-upload');
    const sectionHome = document.getElementById('section-home');
    const sectionBookings = document.getElementById('section-bookings');
    const sectionAccepted = document.getElementById('section-accepted');
    const sectionSecurity = document.getElementById('section-security');
    const sectionUpload = document.getElementById('section-upload');
    const dashboardTitle = document.getElementById('dashboard-title');
    function showSection(section) {
        sectionHome.classList.remove('active');
        sectionBookings.classList.remove('active');
        sectionAccepted.classList.remove('active');
        sectionSecurity.classList.remove('active');
        sectionUpload.classList.remove('active');
        navHome.classList.remove('active');
        navBookings.classList.remove('active');
        navAccepted.classList.remove('active');
        navSecurity.classList.remove('active');
        navUpload.classList.remove('active');
        if (section === 'home') {
            sectionHome.classList.add('active');
            navHome.classList.add('active');
            dashboardTitle.innerText = "Dashboard Home";
        } else if (section === 'bookings') {
            sectionBookings.classList.add('active');
            navBookings.classList.add('active');
            dashboardTitle.innerText = "Bookings Overview";
        } else if (section === 'accepted') {
            sectionAccepted.classList.add('active');
            navAccepted.classList.add('active');
            dashboardTitle.innerText = "Accepted Bookings";
        } else if (section === 'security') {
            sectionSecurity.classList.add('active');
            navSecurity.classList.add('active');
            dashboardTitle.innerText = "Security Manager";
        } else if (section === 'upload') {
            sectionUpload.classList.add('active');
            navUpload.classList.add('active');
            dashboardTitle.innerText = "Upload New Album Picture";
        }
        currentSection = section;
        // Update URL without reload
        if (history.pushState) {
            history.replaceState(null, '', '?section=' + section);
        }
    }
    navHome.onclick = e => { e.preventDefault(); showSection('home'); }
    navBookings.onclick = e => { e.preventDefault(); showSection('bookings'); }
    navAccepted.onclick = e => { e.preventDefault(); showSection('accepted'); }
    navSecurity.onclick = e => { e.preventDefault(); showSection('security'); }
    navUpload.onclick = e => { e.preventDefault(); showSection('upload'); }
    document.getElementById('refreshBtn').onclick = () => window.location.reload();

    // Modal edit for bookings
    const editModal = document.getElementById('editModal');
    const modalOverlay = document.getElementById('modal-overlay');
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Only trigger for bookings edit, not for album rename (album rename is a submit button not a link)
            if (btn.closest('#section-bookings')) {
                e.preventDefault();
                const tr = btn.closest('tr');
                const booking = JSON.parse(tr.dataset.booking);
                document.getElementById('edit-id').value = booking.id;
                document.getElementById('edit-name').value = booking.name;
                document.getElementById('edit-email').value = booking.email;
                document.getElementById('edit-phone').value = booking.phone;
                document.getElementById('edit-service').value = booking.service;
                document.getElementById('edit-message').value = booking.message;
                editModal.classList.add('show');
                modalOverlay.classList.add('show');
            }
        });
    });
    document.getElementById('closeEditModal').onclick = function() {
        editModal.classList.remove('show');
        modalOverlay.classList.remove('show');
    }
    modalOverlay.onclick = function() {
        editModal.classList.remove('show');
        modalOverlay.classList.remove('show');
    }
    document.getElementById("resetBtn").addEventListener("click", function () {
        if (confirm("Are you sure you want to reset the password to default?")) {
            fetch("reset_password.php", { method: "POST" })
                .then(response => response.text())
                .then(data => { alert(data); })
                .catch(error => { alert("An error occurred while resetting the password."); });
        }
    });
</script>
</body>
</html>
<?php $conn->close(); ?>