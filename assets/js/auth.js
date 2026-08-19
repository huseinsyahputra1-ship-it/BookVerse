// Password
const password = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

if (password && togglePassword) {
    togglePassword.addEventListener("click", () => {

        if (password.type === "password") {

            password.type = "text";
            togglePassword.classList.replace("fa-eye", "fa-eye-slash");

        } else {

            password.type = "password";
            togglePassword.classList.replace("fa-eye-slash", "fa-eye");

        }

    });
}

// Confirm Password
const confirmPassword = document.getElementById("confirmPassword");
const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");

if (confirmPassword && toggleConfirmPassword) {

    toggleConfirmPassword.addEventListener("click", () => {

        if (confirmPassword.type === "password") {

            confirmPassword.type = "text";
            toggleConfirmPassword.classList.replace("fa-eye", "fa-eye-slash");

        } else {

            confirmPassword.type = "password";
            toggleConfirmPassword.classList.replace("fa-eye-slash", "fa-eye");

        }

    });

}