<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "bearfruitsstudios";
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("DB error");

function fetch_albums($type) {
    global $conn;
    $albums = [];
    $stmt = $conn->prepare("SELECT * FROM albums WHERE type=? ORDER BY id DESC");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $albums[] = $row;
    $stmt->close();
    return $albums;
}
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

$album_type = isset($_GET['type']) && in_array($_GET['type'], ['wedding','debut']) ? $_GET['type'] : 'wedding';
$albums = fetch_albums($album_type);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BearFruits Studios – <?php echo ucfirst($album_type); ?> Albums</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Fonts: Inter and Dancing Script -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root {
  --primary: #234b3a;
  --accent: #60a5fa;
  --album-gap: 15px;
  --white: #fff;
  --muted: #e7e7e7;
  --shadow: 0 8px 32px 0 rgba(35,75,58,0.14);
  --glass-bg: rgba(255,255,255,0.15);
  --bg: #f8faf7;
}
body {
  font-family: 'Inter', Arial, sans-serif;
  background: var(--bg);
  color: var(--primary);
  margin: 0;
}
a { text-decoration: none; }
.top-navbar {
  width: 100%;
  background: var(--primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 74px;
  padding: 0;
  box-shadow: 0 2px 10px rgba(35,75,58,0.08);
  position: sticky;
  top: 0;
  z-index: 1000;
}
.top-navbar .logo {
  font-size: 2.2rem;
  font-family: 'Dancing Script', cursive, serif;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 12px;
  letter-spacing: 1.4px;
  color: #fff;
}
.top-navbar nav {
  display: flex;
  align-items: center;
  gap: 10px;
}
.top-navbar nav a {
  color: #fff;
  display: flex;
  align-items: center;
  text-decoration: none;
  font-size: 1.14rem;
  font-weight: 500;
  padding: 8px 20px;
  border-radius: 7px;
  margin-left: 8px;
  transition: background 0.18s, color 0.18s, box-shadow .13s;
  border-left: 5px solid transparent;
}
.top-navbar nav a.active, .top-navbar nav a:hover {
  background: #fff;
  color: var(--primary);
  border-left: 5px solid var(--accent);
  box-shadow: 0 2px 8px #e6e9f1;
}
.top-navbar nav a .fa-fw { width: 22px; margin-right: 8px;}
.top-navbar .book-btn {
  background: #ffc107;
  color: #234b3a !important;
  border-radius: 8px;
  font-weight: 600;
  margin-left: 18px;
  padding: 8px 23px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.07);
  border: none;
  transition: background .19s, color .16s, box-shadow .13s;
  font-size: 1.11rem;
  letter-spacing: 0.2px;
}
.top-navbar .book-btn:hover {
  background: #ffd75e;
  color: #234b3a;
  box-shadow: 0 3px 12px #ffd75e7a;
}
.hamburger {
  display: none;
  font-size: 2rem;
  background: none;
  border: none;
  color: #fff;
  margin-left: 14px;
  cursor: pointer;
}
@media (max-width: 900px) {
  .top-navbar { flex-direction: column; align-items: flex-start; height: auto; padding: 7px 2vw; }
  .top-navbar .logo { font-size: 1.35rem; margin-bottom: 6px; }
}
@media (max-width: 700px) {
  .top-navbar nav {
    display: none;
    flex-direction: column;
    width: 100%;
    background: var(--primary);
    margin-top: 5px;
    gap: 0;
  }
  .top-navbar nav.show { display: flex; }
  .top-navbar .logo { font-size: 1.15rem; margin-bottom: 2px; }
  .hamburger { display: block; }
  .top-navbar nav a {
    margin: 0;
    padding: 14px 0 14px 22px;
    border-left: 0;
    border-top: 1px solid rgba(255,255,255,0.10);
    border-radius: 0;
    width: 100%;
  }
  .top-navbar nav a:last-child { border-bottom: 1px solid rgba(255,255,255,0.10);}
}

main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 38px 0 50px 0;
}
.hero-section {
  background: linear-gradient(90deg, #f7fafc 0%, #e6f0ea 100%);
  border-radius: 20px;
  margin-bottom: 38px;
  padding: 48px 30px 38px 30px;
  box-shadow: 0 6px 32px #d7e5dd33;
  text-align: center;
}
.hero-section h1 {
  font-family: 'Dancing Script', cursive, serif;
  font-size: 2.6rem;
  color: var(--primary);
  margin: 0 0 8px 0;
  font-weight: 700;
  letter-spacing: 1.5px;
}
.hero-section p {
  font-size: 1.18rem;
  color: #3b3b3b;
  margin: 0;
  line-height: 1.7;
  font-weight: 400;
}

.album-type-select-row {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 32px;
  margin-top: 18px;
  max-width: 100%;
  background: #f5f6f8;
  border-radius: 13px;
  box-shadow: 0 2px 10px #e7edea47;
  padding: 12px 24px;
}
.album-type-select-row label {
  margin-right: 8px;
  font-size: 1.08rem;
  color: #222;
  font-weight: 500;
}
.album-type-select {
  font-size: 1.06rem;
  padding: 8px 18px;
  border-radius: 7px;
  border: 1.5px solid #e5e7eb;
  background: #f6f7fa;
  color: #222;
  font-family: inherit;
  font-weight: 500;
  outline: none;
  transition: border .18s;
}
.album-type-select:focus {
  border: 1.5px solid var(--accent);
}
.albums-flex {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 34px;
  width: 100%;
}
.album-folder {
  background: #f7fafc;
  border-radius: 16px;
  box-shadow: 0 4px 24px #e7edea75;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  padding: 0 0 22px 0;
  transition: transform .19s, box-shadow .19s;
  border: none;
  position: relative;
  min-width: 0;
}
.album-folder:hover {
  transform: translateY(-6px) scale(1.03);
  box-shadow: 0 12px 32px #bcd0e69c;
}
.album-thumb {
  width: 100%;
  min-height: 200px;
  max-height: 340px;
  aspect-ratio: 4/3;
  border-radius: 16px 16px 0 0;
  object-fit: cover;
  background: #f3f3f3;
  margin-bottom: 0;
  box-shadow: 0 2px 14px #e5e9f1a8;
  transition: filter .15s, box-shadow .15s;
  display: block;
}
.album-folder:hover .album-thumb {
  filter: brightness(1.09) saturate(1.12);
}
.album-name {
  font-family: 'Dancing Script', cursive, serif;
  font-size: 2rem;
  color: var(--primary);
  text-align: center;
  font-weight: 700;
  letter-spacing: 1.2px;
  margin-bottom: 0;
  margin-top: 18px;
  background: none;
  width: 100%;
  line-height: 1.15;
  padding: 0 0 8px 0;
  text-shadow: 0 1.5px 1px #fff, 0 2.5px 15px #e7edea;
  border: none;
}
@media (max-width: 1200px) {
  .album-thumb { min-width: 180px; min-height: 160px; }
  .album-name { font-size: 1.6rem; }
}
@media (max-width: 900px) {
  .albums-flex { gap: 20px;}
  .album-folder { min-width: 0; }
  main { padding: 18px 0 38px 0;}
}
@media (max-width: 700px) {
  .albums-flex { grid-template-columns: 1fr; gap: 18px;}
  .album-folder { min-width: 0; width: 99vw; }
  .album-thumb { min-height: 120px; max-height: 48vw; }
  .album-name { font-size: 1.2rem;}
}

.no-albums {
  color: #bbb;
  font-size: 1.12rem;
  text-align: center;
  margin: 38px auto 30px auto;
  width: 100%;
  background: #fff;
  border-radius: 10px;
  padding: 28px;
  box-shadow: 0 2px 14px #bbb2;
}

/* MODAL GALLERY REDESIGN */
.modal-bg {
  display: none;
  position: fixed;
  z-index: 1001;
  left: 0; top: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,.43);
  animation: fadein 0.22s;
}
@keyframes fadein { from { opacity: 0; } to { opacity: 1; } }
.modal-gallery {
  display: none;
  position: fixed;
  z-index: 1002;
  left: 50%; top: 50%;
  transform: translate(-50%,-50%);
  background: #fff;
  border-radius: 18px;
  padding: 30px 20px 22px 20px;
  box-shadow: 0 18px 64px #bcd0e68f;
  min-width: 320px;
  max-width: 98vw;
  max-height: 92vh;
  overflow: visible;
  animation: fadein .38s;
}
.modal-gallery-title {
  font-family: 'Dancing Script', cursive, serif;
  font-weight: 700;
  font-size: 2rem;
  color: var(--primary);
  text-align: center;
  margin-bottom: 16px;
  letter-spacing: 1.1px;
  text-shadow: 0 1.5px 1px #fff, 0 2.5px 15px #e7edea;
}

.modal-main-image-row {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 18px;
  gap: 8px;
}
.modal-main-image-container {
  background: #f6f7fa;
  border-radius: 12px;
  box-shadow: 0 2px 20px #e5e9f150;
  max-width: 62vw;
  max-height: 56vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}
#modal-main-image {
  max-width: 58vw;
  max-height: 52vh;
  width: auto;
  height: auto;
  border-radius: 10px;
  box-shadow: 0 2px 12px #e5e9f1a0;
  background: #f3f3f3;
  transition: box-shadow .18s, transform .2s;
  object-fit: contain;
  display: block;
  margin: 0 auto;
}
.modal-nav-btn {
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 42px; height: 42px;
  font-size: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background .15s, color .15s;
  opacity: 0.85;
  box-shadow: 0 2px 10px #3331;
}
.modal-nav-btn:hover { background: var(--accent); color: var(--primary); }
.modal-close {
  position: absolute;
  top: 11px; right: 18px;
  font-size: 2rem;
  color: #bbb;
  cursor: pointer;
  background: none;
  border: none;
  font-family: inherit;
  outline: none;
  transition: color .14s;
  z-index: 2;
}
.modal-close:hover { color: var(--accent);}
.modal-thumbnails {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  justify-content: center;
  margin-bottom: 2px;
  margin-top: 1px;
  padding: 4px 0;
}
.modal-thumbnails img {
  width: 70px;
  height: 54px;
  object-fit: cover;
  border-radius: 7px;
  background: #f3f3f3;
  cursor: pointer;
  opacity: 0.7;
  box-shadow: 0 1px 6px #e5e9f1b8;
  border: 2.5px solid transparent;
  transition: opacity .13s, border .13s;
}
.modal-thumbnails img.selected,
.modal-thumbnails img:focus {
  border: 2.5px solid var(--accent);
  opacity: 1;
  outline: none;
}
@media (max-width: 700px) {
  .modal-gallery { max-width: 99vw; }
  .modal-main-image-container { max-width: 98vw; max-height: 45vh; }
  #modal-main-image { max-width: 92vw; max-height: 38vh; }
  .modal-thumbnails img { width: 50px; height: 36px; }
}

/* Enhanced Smaller Social Icons and Footer */
.social {
  text-align: center;
  margin: 40px 0 0 0;
  display: flex;
  flex-direction: row;
  justify-content: center;
  gap: 18px;
}
.social-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  font-size: 1.23em;
  color: var(--primary);
  background: var(--white);
  border: 2px solid var(--primary);
  box-shadow: 0 2px 10px #234b3a16;
  transition: transform 0.18s, box-shadow 0.18s, border 0.18s, color 0.18s, background 0.18s;
  position: relative;
  overflow: hidden;
}
.social-icon i {
  z-index: 2;
  transition: color 0.18s;
}
.social-icon.facebook:hover,
.social-icon.facebook:focus {
  background: #1877f2;
  color: #fff;
  border-color: #1877f2;
  box-shadow: 0 3px 12px #1877f288;
  transform: translateY(-2px) scale(1.07) rotate(-6deg);
}
.social-icon.instagram:hover,
.social-icon.instagram:focus {
  background: radial-gradient(circle at 30% 110%, #fdf497 0%, #f1683a 70%, #bc2a8d 100%);
  color: #fff;
  border-color: #bc2a8d;
  box-shadow: 0 3px 12px #f1683a77;
  transform: translateY(-2px) scale(1.07) rotate(6deg);
}
.social-icon:active {
  transform: scale(0.96);
}
.social-icon::after {
  content: '';
  display: block;
  position: absolute;
  inset: 0;
  border-radius: 50%;
  opacity: 0;
  background: var(--accent);
  z-index: 1;
  transition: opacity 0.20s;
}
.social-icon:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}
footer {
  background: #234b3ad5;
  color: var(--white);
  text-align: center;
  padding: 38px 18px 28px 18px;
  margin-top: 80px;
  font-size: 1.07em;
  letter-spacing: 0.6px;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 -2px 12px #0003;
}
footer p { margin-bottom: 8px; }
@media (max-width: 480px) {
  .social { gap: 10px; }
  .social-icon { width: 32px; height: 32px; font-size: 1em; }
}
</style>
</head>
<body>
<div class="top-navbar">
  <div class="logo"><i class="fa-solid fa-leaf"></i> BearFruits Studios</div>
  <button class="hamburger" id="hamburgerBtn" aria-label="Menu"><i class="fa fa-bars"></i></button>
  <nav id="mainNav">
    <a href="homepage.html"><i class="fa-fw fa-solid fa-house"></i>Home</a>
    <a href="about.html"><i class="fa-fw fa-solid fa-user"></i>About</a>
    <a href="http://localhost/project1/book.html" class="book-btn"><i class="fa-fw fa-solid fa-calendar-check"></i>Book Now</a>
  </nav>
</div>
<main>
  <section class="hero-section">
    <h1><?php echo ucfirst($album_type); ?> Albums</h1>
    <p>
      Explore our carefully crafted and artistically captured <b><?php echo $album_type; ?></b> moments.<br>
      Click on an album below to view the full gallery.
    </p>
  </section>
  <form class="album-type-select-row" method="get" id="albumTypeForm" autocomplete="off">
    <label for="type">Album type:</label>
    <select name="type" id="type" class="album-type-select" onchange="document.getElementById('albumTypeForm').submit();">
      <option value="wedding" <?php if($album_type=='wedding') echo 'selected'; ?>>Wedding</option>
      <option value="debut" <?php if($album_type=='debut') echo 'selected'; ?>>Debut</option>
    </select>
  </form>
  <div class="albums-flex">
    <?php if(empty($albums)): ?>
      <div class="no-albums">No albums found for this category.</div>
    <?php endif; ?>
    <?php foreach($albums as $album):
      $thumb = $album['thumbnail'] ? "uploads/albums/{$album['id']}/{$album['thumbnail']}" : "https://images.unsplash.com/photo-1519125323398-675f0ddb6308?fit=crop&w=500&q=80";
      $images = get_album_images($album['id']);
    ?>
      <div class="album-folder" data-album-id="<?php echo $album['id']; ?>" data-album-name="<?php echo htmlspecialchars($album['name']); ?>" data-album-images='<?php echo json_encode($images); ?>'>
        <img src="<?php echo $thumb ?>" class="album-thumb" alt="<?php echo htmlspecialchars($album['name']); ?>">
        <div class="album-name"><?php echo htmlspecialchars($album['name']); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <!-- Modal for album images -->
  <div class="modal-bg" id="modal-bg"></div>
  <div class="modal-gallery" id="modal-gallery" tabindex="-1" aria-modal="true" role="dialog">
    <button class="modal-close" id="modal-close" title="Close">&times;</button>
    <div class="modal-gallery-title" id="modal-gallery-title"></div>
    <div class="modal-main-image-row">
      <button class="modal-nav-btn" id="modal-prev" title="Previous"><i class="fa fa-chevron-left"></i></button>
      <div class="modal-main-image-container">
        <img id="modal-main-image" src="" alt="" />
      </div>
      <button class="modal-nav-btn" id="modal-next" title="Next"><i class="fa fa-chevron-right"></i></button>
    </div>
    <div class="modal-thumbnails" id="modal-thumbnails"></div>
  </div>
</main>

<!-- Footer with enhanced social icons (copied from about.html) -->
<div class="social fade-in delay-4">
  <a href="https://www.facebook.com/thebearfruitstudios" target="_blank" aria-label="Facebook" class="social-icon facebook">
    <i class="fab fa-facebook-f"></i>
  </a>
  <a href="https://www.instagram.com/thebearfruitstudios" target="_blank" aria-label="Instagram" class="social-icon instagram">
    <i class="fab fa-instagram"></i>
  </a>
</div>
<footer class="fade-in delay-6">
  <p>&copy; 2025 The BearFruit Studios</p>
  <p>Crafted with passion &amp; creativity · All rights reserved</p>
</footer>
<script>
const hamburger = document.getElementById('hamburgerBtn');
const mainNav = document.getElementById('mainNav');
hamburger.addEventListener('click', function(){
  mainNav.classList.toggle('show');
});
window.addEventListener('resize', function(){
  if(window.innerWidth > 700) mainNav.classList.remove('show');
});

// MODAL GALLERY REDESIGN LOGIC
let currentModalIndex = 0;
let currentModalImages = [];
let currentModalAlbumId = '';

document.querySelectorAll('.album-folder').forEach(function(el){
  el.addEventListener('click', function(){
    currentModalAlbumId = el.getAttribute('data-album-id');
    const albumName = el.getAttribute('data-album-name');
    currentModalImages = JSON.parse(el.getAttribute('data-album-images'));
    if (currentModalImages.length === 0) {
      document.getElementById('modal-gallery-title').textContent = albumName;
      document.getElementById('modal-main-image').src = '';
      document.getElementById('modal-thumbnails').innerHTML = '<div style="color:#bbb; margin:20px;">No images in this album.</div>';
      document.getElementById('modal-prev').style.display = 'none';
      document.getElementById('modal-next').style.display = 'none';
    } else {
      currentModalIndex = 0;
      renderModalGallery(albumName);
    }
    document.getElementById('modal-bg').style.display = 'block';
    document.getElementById('modal-gallery').style.display = 'block';
    setTimeout(()=>{document.getElementById('modal-gallery').focus();},60);
  });
});

function renderModalGallery(albumName) {
  // Title
  document.getElementById('modal-gallery-title').textContent = albumName;
  // Main image
  const mainImg = document.getElementById('modal-main-image');
  const src = 'uploads/albums/' + currentModalAlbumId + '/' + encodeURIComponent(currentModalImages[currentModalIndex]);
  mainImg.src = src;
  mainImg.alt = albumName;
  // Thumbnails
  const thumbHtml = currentModalImages.map((img, idx) => 
    `<img tabindex="0" src="uploads/albums/${currentModalAlbumId}/${encodeURIComponent(img)}" alt="thumb" class="${idx===currentModalIndex?'selected':''}" data-index="${idx}">`
  ).join('');
  document.getElementById('modal-thumbnails').innerHTML = thumbHtml;
  document.querySelectorAll('#modal-thumbnails img').forEach(img => {
    img.onclick = () => { currentModalIndex = Number(img.getAttribute('data-index')); renderModalGallery(albumName); }
    img.onkeydown = (e) => { if(e.key==='Enter' || e.key===' ') { e.preventDefault(); img.click(); } }
  });
  // Arrow navigation display
  document.getElementById('modal-prev').style.display = currentModalImages.length>1?'':'none';
  document.getElementById('modal-next').style.display = currentModalImages.length>1?'':'none';
}

document.getElementById('modal-prev').onclick = function() {
  if (currentModalImages.length) {
    currentModalIndex = (currentModalIndex + currentModalImages.length - 1) % currentModalImages.length;
    renderModalGallery(document.getElementById('modal-gallery-title').textContent);
  }
};
document.getElementById('modal-next').onclick = function() {
  if (currentModalImages.length) {
    currentModalIndex = (currentModalIndex + 1) % currentModalImages.length;
    renderModalGallery(document.getElementById('modal-gallery-title').textContent);
  }
};
document.getElementById('modal-main-image').onclick = function() {
  if (this.src) window.open(this.src, '_blank');
};
document.getElementById('modal-bg').onclick = closeModal;
document.getElementById('modal-close').onclick = closeModal;
function closeModal() {
  document.getElementById('modal-bg').style.display = 'none';
  document.getElementById('modal-gallery').style.display = 'none';
}
document.addEventListener('keydown', function(e){
  // Left/Right arrow navigation, modal close on Esc
  if(document.getElementById('modal-gallery').style.display === 'block'){
    if(e.key === 'ArrowLeft') document.getElementById('modal-prev').click();
    if(e.key === 'ArrowRight') document.getElementById('modal-next').click();
    if(e.key === 'Escape') closeModal();
  }
});
</script>
</body>
</html>
<?php $conn->close(); ?>