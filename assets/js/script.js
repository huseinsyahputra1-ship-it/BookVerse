const searchInput = document.getElementById("searchInput");
const books = document.querySelectorAll(".book-card");

searchInput.addEventListener("keyup", function () {
    const keyword = this.value.toLowerCase();

    books.forEach(book => {
        const title = book.querySelector("h3").textContent.toLowerCase();
        const author = book.querySelector("p").textContent.toLowerCase();

        console.log("Judul:", title, "| Author:", author);

        if (title.includes(keyword) || author.includes(keyword)) {
            book.style.display = "";
        } else {
            book.style.display = "none";
        }
    });
});

// ================= PROFILE DROPDOWN =================

const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

if (profileBtn && profileDropdown) {

    profileBtn.addEventListener("click", function (event) {

        event.stopPropagation();

        profileDropdown.classList.toggle("show");

    });

    document.addEventListener("click", function (event) {

        if (!profileDropdown.contains(event.target) &&
            !profileBtn.contains(event.target)) {

            profileDropdown.classList.remove("show");

        }

    });

}