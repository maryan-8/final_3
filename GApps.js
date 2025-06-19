// Scroll to Top Button
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Scroll event for navbar style changes and scroll up button visibility
window.onscroll = function () {
    const navbar = document.getElementById("navbar");
    const scrollUpArrow = document.getElementById("scrollUpArrow");
    if (document.body.scrollTop > 1000 || document.documentElement.scrollTop > 1000) {
        navbar.classList.add("scrolled");
        scrollUpArrow.style.display = "block";
    } else {
        navbar.classList.remove("scrolled");
        scrollUpArrow.style.display = "none";
    }
};

// Handle album option switching
document.getElementById('weddingButton').onclick = function () {
    document.getElementById('weddingFolders').classList.add('active');
    document.getElementById('debutFolders').classList.remove('active');
    document.getElementById('weddingButton').classList.add('active');
    document.getElementById('debutButton').classList.remove('active');
};

document.getElementById('debutButton').onclick = function () {
    document.getElementById('debutFolders').classList.add('active');
    document.getElementById('weddingFolders').classList.remove('active');
    document.getElementById('debutButton').classList.add('active');
    document.getElementById('weddingButton').classList.remove('active');
};

// Default to Wedding albums on page load
window.onload = function() {
    document.getElementById('weddingFolders').classList.add('active');
    document.getElementById('weddingButton').classList.add('active');
};
